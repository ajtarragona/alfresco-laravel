<?php
namespace Ajtarragona\AlfrescoLaravel\Models\Vendor\Cmis;
use stdClass;
use DOMDocument;
use DomXPath;

use Ajtarragona\AlfrescoLaravel\Models\Vendor\Cmis\Exceptions\CmisInvalidArgumentException;
use Ajtarragona\AlfrescoLaravel\Models\Vendor\Cmis\Exceptions\CmisObjectNotFoundException;
use Ajtarragona\AlfrescoLaravel\Models\Vendor\Cmis\Exceptions\CmisPermissionDeniedException;
use Ajtarragona\AlfrescoLaravel\Models\Vendor\Cmis\Exceptions\CmisNotSupportedException;
use Ajtarragona\AlfrescoLaravel\Models\Vendor\Cmis\Exceptions\CmisConstraintException;
use Ajtarragona\AlfrescoLaravel\Models\Vendor\Cmis\Exceptions\CmisRuntimeException;
use Ajtarragona\AlfrescoLaravel\Models\Vendor\Cmis\Exceptions\CmisNotImplementedException;
use Illuminate\Support\Str;


class CmisRepositoryWrapper
{
    
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_ACCEPTED = 202;
    const HTTP_NONAUTHORITATIVE_INFORMATION = 203;
    const HTTP_NO_CONTENT = 204;
    const HTTP_RESET_CONTENT = 205;
    const HTTP_PARTIAL_CONTENT = 206;
    const HTTP_MULTIPLE_CHOICES = 300;
    const HTTP_BAD_REQUEST = 400; // invalidArgument, filterNotValid
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_FORBIDDEN = 403; // permissionDenied, streamNotSupported
    const HTTP_NOT_FOUND = 404; // objectNotFound
    const HTTP_METHOD_NOT_ALLOWED = 405; // notSupported
    const HTTP_NOT_ACCEPTABLE = 406;
    const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    const xHTTP_REQUEST_TIMEOUT = 408; //Had to change this b/c HTTP_REQUEST_TIMEOUT conflicts with definition in Drupal 7
    const HTTP_CONFLICT = 409; // constraint, contentAlreadyExists, versioning, updateConflict, nameConstraintViolation
    const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    const HTTP_UNPROCESSABLE_ENTITY = 422;
    const HTTP_INTERNAL_SERVER_ERROR = 500; // runtime, storage




    // Handles --
    //   Workspace -- but only endpoints with a single repo
    //   Entry -- but only for objects
    //   Feeds -- but only for non-hierarchical feeds
    // Does not handle --
    //   -- Hierarchical Feeds
    //   -- Types
    //   -- Others?
    // Only Handles Basic Auth
    // Very Little Error Checking
    // Does not work against pre CMIS 1.0 Repos
    
    
    
    protected $url;
    protected $username;
    protected $password;
    protected $authenticated;
    protected $workspace;
    protected $last_request;
    protected $do_not_urlencode;
    protected $_addlCurlOptions = array();
    
    
    
    static $namespaces = array (
        "cmis" => "http://docs.oasis-open.org/ns/cmis/core/200908/",
        "cmisra" => "http://docs.oasis-open.org/ns/cmis/restatom/200908/",
        "atom" => "http://www.w3.org/2005/Atom",
        "app" => "http://www.w3.org/2007/app",
        
    );



   public function __construct($url, $username = null, $password = null, $options = null, array $addlCurlOptions = array())
    {
        if (is_array($options) && $options["config:do_not_urlencode"]) {
            $this->do_not_urlencode=true;
        }
        $this->_addlCurlOptions = $addlCurlOptions; // additional cURL options
        
        $this->connect($url, $username, $password, $options);
    }


    protected static function getAsArray($prop) {
    	if ($prop == null) {
			return array();
		} elseif (!is_array($prop)) {
			return array($prop);
		} else {
			return($prop);
		}
		    	
    }


    protected static function getOpUrl($url, $options = null)
    {
        if (is_array($options) && (count($options) > 0))
        {
            $needs_question = strstr($url, "?") === false;
            return $url . ($needs_question ? "?" : "&") . http_build_query($options);
        } else
        {
            return $url;
        }
    }
    

    protected function convertStatusCode($code, $message)
    {
        switch ($code) {
            case self::HTTP_BAD_REQUEST:
                return new CmisInvalidArgumentException($message, $code);
            case self::HTTP_NOT_FOUND:
                return new CmisObjectNotFoundException($message, $code);
            case self::HTTP_FORBIDDEN:
                return new CmisPermissionDeniedException($message, $code);
            case self::HTTP_METHOD_NOT_ALLOWED:
                return new CmisNotSupportedException($message, $code);
            case self::HTTP_CONFLICT:
                return new CmisConstraintException($message, $code);
            default:
                return new CmisRuntimeException($message, $code);
            }
    }


    protected function connect($url, $username, $password, $options)
    {
        // TODO: Make this work with cookies
        $this->url = $url;
        $this->username = $username;
        $this->password = $password;
        $this->auth_options = $options;
        $this->authenticated = false;
        $retval = $this->doGet($this->url);
        if ($retval->code == self::HTTP_OK || $retval->code == self::HTTP_CREATED)
        {
            $this->authenticated = true;
            $this->workspace = self :: extractWorkspace($retval->body);
        }
    }


    protected function doGet($url)
    {
        $retval = $this->doRequest($url);
        if ($retval->code != self::HTTP_OK)
        {
            throw $this->convertStatusCode($retval->code, $retval->body);
        }
        return $retval;
    }


    protected function doDelete($url)
    {
        $retval = $this->doRequest($url, "DELETE");
        if ($retval->code != self::HTTP_NO_CONTENT)
        {
            throw $this->convertStatusCode($retval->code, $retval->body);
        }
        return $retval;
    }


    protected function doPost($url, $content, $contentType, $charset = null)
    {
       
        
        $retval = $this->doRequest($url, "POST", $content, $contentType);

        if ($retval->code != self::HTTP_CREATED)
        {
            throw $this->convertStatusCode($retval->code, $retval->body);
        }
        return $retval;
    }


    protected function doPut($url, $content, $contentType, $charset = null)
    {
        $retval = $this->doRequest($url, "PUT", $content, $contentType);
        if (($retval->code < self::HTTP_OK) || ($retval->code >= self::HTTP_MULTIPLE_CHOICES))
        {
            throw $this->convertStatusCode($retval->code, $retval->body);
        }
        return $retval;
    }


    protected function doRequest($url, $method = "GET", $content = null, $contentType = null, $charset = null)
    {
        
        
        // Process the HTTP request
        // 'til now only the GET request has been tested
        // Does not URL encode any inputs yet
        if (is_array($this->auth_options))
        {
            $url = self :: getOpUrl($url, $this->auth_options);
        }
        
        $session = curl_init($url);
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        if ($this->username)
        {
            curl_setopt($session, CURLOPT_USERPWD, $this->username . ":" . $this->password);
        }
        curl_setopt($session, CURLOPT_CUSTOMREQUEST, $method);
        if ($contentType)
        {
            curl_setopt($session, CURLOPT_HTTPHEADER, array (
                "Content-Type: " . $contentType
            ));
        }

        if ($content)
        {
            curl_setopt($session, CURLOPT_POSTFIELDS, $content);
        }

        if ($method == "POST")
        {
            curl_setopt($session, CURLOPT_POST, true);
        }
        

        // apply addl. cURL options
        // WARNING: this may override previously set options
        if (count($this->_addlCurlOptions)) {
            foreach ($this->_addlCurlOptions as $key => $value) {
                curl_setopt($session, $key, $value);
            }
        }
        
        
        //TODO: Make this storage optional
        $retval = new stdClass();
        $retval->url = $url;
        $retval->method = $method;
        $retval->content_sent = $content;
        $retval->content_type_sent = $contentType;

        $retval->body = curl_exec($session);
        $retval->code = curl_getinfo($session, CURLINFO_HTTP_CODE);
        $retval->content_type = curl_getinfo($session, CURLINFO_CONTENT_TYPE);
        $retval->content_length = curl_getinfo($session, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($session);
        $this->last_request = $retval;
        return $retval;
    }

    protected function getLastRequest()
    {
        return $this->last_request;
    }

    protected function getLastRequestBody()
    {
        return $this->last_request->body;
    }

    protected function getLastRequestCode()
    {
        return $this->last_request->code;
    }

    protected function getLastRequestContentType()
    {
        return $this->last_request->content_type;
    }

    protected function getLastRequestContentLength()
    {
        return $this->last_request->content_length;
    }

    protected function getLastRequestURL()
    {
        return $this->last_request->url;
    }

    protected function getLastRequestMethod()
    {
        return $this->last_request->method;
    }

    protected function getLastRequestContentTypeSent()
    {
        return $this->last_request->content_type_sent;
    }

    protected function getLastRequestContentSent()
    {
        return $this->last_request->content_sent;
    }

    // Static Utility Functions

    protected static function processTemplate($template, $values = array ())
    {
        // Fill in the blanks -- 
        $retval = $template;
        if (is_array($values))
        {
            foreach ($values as $name => $value)
            {
                $retval = Str::replace("{" . $name . "}", $value, $retval);
            }
        }
        // Fill in any unpoupated variables with ""
        return preg_replace("/{[a-zA-Z0-9_]+}/", "", $retval);

    }


    protected static function doXQuery($xmldata, $xquery)
    {
        $doc = new DOMDocument();
        $doc->loadXML($xmldata);
        return self :: doXQueryFromNode($doc, $xquery);
    }


    protected static function doXQueryFromNode($xmlnode, $xquery)
    {
        // Perform an XQUERY on a NODE
        // Register the 4 CMIS namespaces
        //THis may be a hopeless HACK!
        //TODO: Review
        if (!($xmlnode instanceof DOMDocument)) {
            $xdoc=new DOMDocument();
            $xnode = $xdoc->importNode($xmlnode,true);
            $xdoc->appendChild($xnode);
            $xpath = new DomXPath($xdoc);
        } else {
        	$xpath = new DomXPath($xmlnode);
        }
        foreach (self :: $namespaces as $nspre => $nsuri)
        {
            $xpath->registerNamespace($nspre, $nsuri);
        }
        return $xpath->query($xquery);

    }


    protected static function getLinksArray($xmlnode)
    {
        // Gets the links of an object or a workspace
        // Distinguishes between the two "down" links
        //  -- the children link is put into the associative array with the "down" index
        //  -- the descendants link is put into the associative array with the "down-tree" index
        //  These links are distinquished by the mime type attribute, but these are probably the only two links that share the same rel ..
        //    so this was done as a one off
        $links = array ();
        $link_nodes = $xmlnode->getElementsByTagName("link");
        foreach ($link_nodes as $ln)
        {
            if ($ln->attributes->getNamedItem("rel")->nodeValue == "down" && $ln->attributes->getNamedItem("type")->nodeValue == "application/cmistree+xml")
            {
                //Descendents and Childredn share same "rel" but different document type
                $links["down-tree"] = $ln->attributes->getNamedItem("href")->nodeValue;
            } else
            {
                $links[$ln->attributes->getNamedItem("rel")->nodeValue] = $ln->attributes->getNamedItem("href")->nodeValue;
            }
        }
        return $links;
    }


	protected static function extractAllowableActions($xmldata)
    {
        $doc = new DOMDocument();
        $doc->loadXML($xmldata);
        return self :: extractAllowableActionsFromNode($doc);
    }


    protected static function extractAllowableActionsFromNode($xmlnode)
    {
        $result = array();
        $allowableActions = $xmlnode->getElementsByTagName("allowableActions");
        if ($allowableActions->length > 0) {
            foreach($allowableActions->item(0)->childNodes as $action)
            {
                if (isset($action->localName)) {
                    $result[$action->localName] = (preg_match("/^true$/i", $action->nodeValue) > 0);
                }
            }
        }
        return $result;
    }


    protected static function extractObject($xmldata)
    {
        $doc = new DOMDocument();
        $doc->loadXML($xmldata);
        return self :: extractObjectFromNode($doc);

    }


    protected static function extractObjectFromNode($xmlnode)
    {
        // Extracts the contents of an Object and organizes them into:
        //  -- Links
        //  -- Properties
        //  -- the Object ID
        // RRM -- NEED TO ADD ALLOWABLEACTIONS
        $retval = new stdClass();
        $retval->links = self :: getLinksArray($xmlnode);
        $retval->properties = array ();
        $renditions = $xmlnode->getElementsByTagName("object")->item(0)->getElementsByTagName("rendition");
		// Add renditions to CMIS object
		$renditionArray = array();
		if($renditions->length > 0){
		  $i = 0;
		  foreach ($renditions as $rendition) {
		    $rend_nodes = $rendition->childNodes;
            foreach ($rend_nodes as $rend){
              if ($rend->localName != NULL){
	            $renditionArray[$i][$rend->localName] = $rend->nodeValue;
              }
            }
            $i++;        
	      }
		}
		$retval->renditions = $renditionArray;
        
        $prop_nodes = $xmlnode->getElementsByTagName("object")->item(0)->getElementsByTagName("properties")->item(0)->childNodes;
        foreach ($prop_nodes as $pn)
        {
        	if ($pn->attributes) {
				//supressing errors since PHP sometimes sees DOM elements as "non-objects"
                if(isset($pn->attributes->getNamedItem("propertyDefinitionId")->nodeValue) && isset($pn->getElementsByTagName("value")->item(0)->nodeValue)){
				    @$retval->properties[$pn->attributes->getNamedItem("propertyDefinitionId")->nodeValue] = $pn->getElementsByTagName("value")->item(0)->nodeValue;
                }
			}
        }
        
        $retval->uuid = $xmlnode->getElementsByTagName("id")->item(0)->nodeValue;
        $retval->id = $retval->properties["cmis:objectId"];
        //TODO: RRM FIX THIS
        $children_node = $xmlnode->getElementsByTagName("children");
        if (is_object($children_node)) {
        	    $children_feed_c = $children_node->item(0);
        }
        if (is_object($children_feed_c)) {
			$children_feed_l = $children_feed_c->getElementsByTagName("feed");
        }
        if (isset($children_feed_l) && is_object($children_feed_l) && is_object($children_feed_l->item(0))) {
        	$children_feed = $children_feed_l->item(0);
			$children_doc = new DOMDocument();
			$xnode = $children_doc->importNode($children_feed,true); // Avoid Wrong Document Error
			$children_doc->appendChild($xnode);
	        $retval->children = self :: extractObjectFeedFromNode($children_doc);
        }
		$retval->allowableActions = self :: extractAllowableActionsFromNode($xmlnode);
        return $retval;
    }
    

    protected static function extractTypeDef($xmldata)
    {
        $doc = new DOMDocument();
        $doc->loadXML($xmldata);
        return self :: extractTypeDefFromNode($doc);

    }


    protected static function extractTypeDefFromNode($xmlnode)
    {
        // Extracts the contents of an Object and organizes them into:
        //  -- Links
        //  -- Properties
        //  -- the Object ID
        // RRM -- NEED TO ADD ALLOWABLEACTIONS
        $retval = new stdClass();
        $retval->links = self :: getLinksArray($xmlnode);
        $retval->properties = array ();
        $retval->attributes = array ();
        $result = self :: doXQueryFromNode($xmlnode, "//cmisra:type/*");
        foreach ($result as $node)
        {
            if ((substr($node->nodeName, 0, 13) == "cmis:property") && (substr($node->nodeName, -10) == "Definition"))
            {
                $id = $node->getElementsByTagName("id")->item(0)->nodeValue;
                $cardinality = $node->getElementsByTagName("cardinality")->item(0)->nodeValue;
                $propertyType = $node->getElementsByTagName("propertyType")->item(0)->nodeValue;
                // Stop Gap for now
                $retval->properties[$id] = array (
                    "cmis:propertyType" => $propertyType,
                    "cmis:cardinality" => $cardinality,
                    
                );
            } else
            {
                $retval->attributes[$node->nodeName] = $node->nodeValue;
            }
            $retval->id = $retval->attributes["cmis:id"];
        }
        //TODO: RRM FIX THIS
        $children_node = $xmlnode->getElementsByTagName("children");
        if (is_object($children_node)) {
        	    $children_feed_c = $children_node->item(0);
        }
        if (is_object($children_feed_c)) {
			$children_feed_l = $children_feed_c->getElementsByTagName("feed");
        }
        if (isset($childern_feed_l) && is_object($children_feed_l) && is_object($children_feed_l->item(0))) {
        	$children_feed = $children_feed_l->item(0);
			$children_doc = new DOMDocument();
			$xnode = $children_doc->importNode($children_feed,true); // Avoid Wrong Document Error
			$children_doc->appendChild($xnode);
	        $retval->children = self :: extractTypeFeedFromNode($children_doc);
        }

        /*
         * 
        
        
        
        		$prop_nodes = $xmlnode->getElementsByTagName("object")->item(0)->getElementsByTagName("properties")->item(0)->childNodes;
        		foreach ($prop_nodes as $pn) {
        			if ($pn->attributes) {
        				$retval->properties[$pn->attributes->getNamedItem("propertyDefinitionId")->nodeValue] = $pn->getElementsByTagName("value")->item(0)->nodeValue;
        			}
        		}
                $retval->uuid=$xmlnode->getElementsByTagName("id")->item(0)->nodeValue;
                $retval->id=$retval->properties["cmis:objectId"];
         */
        return $retval;
    }


    protected static function extractObjectFeed($xmldata)
    {
        //Assumes only one workspace for now
        $doc = new DOMDocument();
        if($xmldata) $doc->loadXML($xmldata); //TXO
        return self :: extractObjectFeedFromNode($doc);
    }


    protected static function extractObjectFeedFromNode($xmlnode)
    {
        // Process a feed and extract the objects
        //   Does not handle hierarchy
        //   Provides two arrays 
        //   -- one sequential array (a list)
        //   -- one hash table indexed by objectID
        //   and a property "numItems" that holds the total number of items available.
        $retval = new stdClass();
        // extract total number of items
        $numItemsNode = self::doXQueryFromNode($xmlnode, "/atom:feed/cmisra:numItems");
        $retval->numItems = $numItemsNode->length ? (int) $numItemsNode->item(0)->nodeValue : -1; // set to negative value if info is not available
                
        $retval->objectList = array ();
        $retval->objectsById = array ();
        $result = self :: doXQueryFromNode($xmlnode, "/atom:feed/atom:entry");
        foreach ($result as $node)
        {
            $obj = self :: extractObjectFromNode($node);
            $retval->objectsById[$obj->id] = $obj;
            $retval->objectList[] = & $retval->objectsById[$obj->id];
        }
        return $retval;
    }


    protected static function extractTypeFeed($xmldata)
    {
        //Assumes only one workspace for now
        $doc = new DOMDocument();
        $doc->loadXML($xmldata);
        return self :: extractTypeFeedFromNode($doc);
    }


    protected static function extractTypeFeedFromNode($xmlnode)
    {
        // Process a feed and extract the objects
        //   Does not handle hierarchy
        //   Provides two arrays 
        //   -- one sequential array (a list)
        //   -- one hash table indexed by objectID
        $retval = new stdClass();
        $retval->objectList = array ();
        $retval->objectsById = array ();
        $result = self :: doXQueryFromNode($xmlnode, "/atom:feed/atom:entry");
        foreach ($result as $node)
        {
            $obj = self :: extractTypeDefFromNode($node);
            $retval->objectsById[$obj->id] = $obj;
            $retval->objectList[] = & $retval->objectsById[$obj->id];
        }
        return $retval;
    }


    protected static function extractWorkspace($xmldata)
    {
        //Assumes only one workspace for now
        $doc = new DOMDocument();
        $doc->loadXML($xmldata);
        return self :: extractWorkspaceFromNode($doc);
    }


    protected static function extractWorkspaceFromNode($xmlnode)
    {
        // Assumes only one workspace for now
        // Load up the workspace object with arrays of
        //  links
        //  URI Templates
        //  Collections
        //  Capabilities
        //  General Repository Information
        $retval = new stdClass();
        $retval->links = self :: getLinksArray($xmlnode);
        $retval->uritemplates = array ();
        $retval->collections = array ();
        $retval->capabilities = array ();
        $retval->repositoryInfo = array ();
        $retval->permissions = array();
        $retval->permissionsMapping = array();
        $result = self :: doXQueryFromNode($xmlnode, "//cmisra:uritemplate");
        foreach ($result as $node)
        {
            $retval->uritemplates[$node->getElementsByTagName("type")->item(0)->nodeValue] = $node->getElementsByTagName("template")->item(0)->nodeValue;
        }
        $result = self :: doXQueryFromNode($xmlnode, "//app:collection");
        foreach ($result as $node)
        {
            $retval->collections[$node->getElementsByTagName("collectionType")->item(0)->nodeValue] = $node->attributes->getNamedItem("href")->nodeValue;
        }
        $result = self :: doXQueryFromNode($xmlnode, "//cmis:capabilities/*");
        foreach ($result as $node)
        {
            $retval->capabilities[$node->nodeName] = $node->nodeValue;
        }
        $result = self :: doXQueryFromNode($xmlnode, "//cmisra:repositoryInfo/*[name()!='cmis:capabilities' and name()!='cmis:aclCapability']");
        foreach ($result as $node)
        {
            $retval->repositoryInfo[$node->nodeName] = $node->nodeValue;
        }
        $result = self :: doXQueryFromNode($xmlnode, "//cmis:aclCapability/cmis:permissions");
        foreach ($result as $node)
        {
            $retval->permissions[$node->getElementsByTagName("permission")->item(0)->nodeValue] = $node->getElementsByTagName("description")->item(0)->nodeValue;
        }
        $result = self :: doXQueryFromNode($xmlnode, "//cmis:aclCapability/cmis:mapping");
        foreach ($result as $node)
        {
            $key = $node->getElementsByTagName("key")->item(0)->nodeValue;
            $values = array();
            foreach ($node->getElementsByTagName("permission") as $value)
            {
                array_push($values, $value->nodeValue);
            }
            $retval->permissionsMapping[$key] = $values;
        }
        $result = self :: doXQueryFromNode($xmlnode, "//cmis:aclCapability/*[name()!='cmis:permissions' and name()!='cmis:mapping']");
        foreach ($result as $node)
        {
            $retval->repositoryInfo[$node->nodeName] = $node->nodeValue;
        }

        return $retval;
    }
}
