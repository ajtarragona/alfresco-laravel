@if($folders)
	<ul>
		{{-- @dump($folder) --}}
		@foreach($folders as $thisfolder)
			<li class="folder {{$thisfolder->id==$folder->id?"disabled":""}} {{($thisfolder->getFolders()?'has-children':'')}}" data-id="{{$thisfolder->id}}">
				<span class="folder-node">
					<span class="opener">{!! $thisfolder->renderIcon() !!}</span> 
					<span class="folder-text">{{ $thisfolder->name }}</span>
				</span>
				{{-- @dump($folder->getChildren('folder')) --}}
			</li>
		@endforeach
	</ul>
@endif