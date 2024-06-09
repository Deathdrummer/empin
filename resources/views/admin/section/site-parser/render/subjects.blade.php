@if($init)
	<div class="row">
		<div class="col-auto w60">
			
			<div class="row row-cols-15 mb2rem gy-10" subjectlettersblock>
				@foreach(['а', 'б', 'в', 'г', 'д', 'е', 'ж', 'з', 'и', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ы', 'э', 'ю', 'я', '...'] as $lt)
					<div class="col-auto">
						<div
							class="d-flex align-items-center justify-content-center border-radius-3px bg-light w2rem-2px h2rem bg-light-hovered"
							subjectletter="{{$lt}}"
							{{$letter == $lt ? 'selected' : ''}}
							onclick="$.chooseSubjectLetter(this)"
							><p class="text-center lh120">{{$lt}}</p>
						</div>
					</div>
				@endforeach
			</div>
		</div>
		<div class="col">
			<x-input group="normal" placeholder="название категории" class="w100" oninput="$.searchSubject(event)" />
		</div>
	</div>
			


	<x-table class="w100" id="subjectsTable" scrolled="calc(100vh - 290px)" scrollend="scrollSubjects" noborder>
		<x-table.head noborder>
			<x-table.tr noborder>
				<x-table.td class="w5rem h-center" noborder><strong>№</strong></x-table.td>
				<x-table.td class="w-auto" noborder><strong onclick="$.sortSubjects('subject')" class="pointer color-gray-600-hovered color-blue-active">Тематика</strong></x-table.td>
				<x-table.td class="w10rem" noborder><strong onclick="$.sortSubjects('contacts_count')" class="pointer color-gray-600-hovered color-blue-active">Кол-во контактов</strong></x-table.td>
			</x-table.tr>
		</x-table.head>
		<x-table.body class="minh-20rem" id="subjectsList">
				@forelse($subjects as $key => $subject)
					@if($subject['selected'] ?? false)
						<x-table.tr class="h4rem" noborder hovered onclick="toggleAttribute('selected')" selected subject="{{$subject['id']}}">
							<x-table.td><p>{{$key + 1}}</p></x-table.td>
							<x-table.td><p class="lh90 breakword" subjecttitle>{{$subject['subject']}}</p></x-table.td>
							<x-table.td><p class="lh90">{{$subject['contacts_count']}}</p></x-table.td>
						</x-table.tr>
					@else
						<x-table.tr class="h4rem" noborder hovered onclick="toggleAttribute('selected')" subject="{{$subject['id']}}">
							<x-table.td><p>{{$key + 1}}</p></x-table.td>
							<x-table.td><p class="lh90 breakword" subjecttitle>{{$subject['subject']}}</p></x-table.td>
							<x-table.td><p class="lh90">{{$subject['contacts_count']}}</p></x-table.td>
						</x-table.tr>
					@endif
				@empty
					<x-table.tr class="h4rem d-flex align-items-center justify-content-center" noborder>
						<p class="color-gray-400">Нет данных</p>
					</x-table.tr>
				@endforelse
		</x-table.body>
	</x-table>
@else
	@forelse($subjects as $key => $subject)
		@if($subject['selected'] ?? false)
			<x-table.tr class="h4rem" noborder hovered onclick="toggleAttribute('selected')" selected subject="{{$subject['id']}}">
				<x-table.td><p>{{$key + 1 + ($offset * $part)}}</p></x-table.td>
				<x-table.td><p class="lh90 breakword" subjecttitle>{{$subject['subject']}}</p></x-table.td>
				<x-table.td><p class="lh90">{{$subject['contacts_count']}}</p></x-table.td>
			</x-table.tr>
		@else
			<x-table.tr class="h4rem" noborder hovered onclick="toggleAttribute('selected')" subject="{{$subject['id']}}">
				<x-table.td><p>{{$key + 1 + ($offset * $part)}}</p></x-table.td>
				<x-table.td><p class="lh90 breakword" subjecttitle>{{$subject['subject']}}</p></x-table.td>
				<x-table.td><p class="lh90">{{$subject['contacts_count']}}</p></x-table.td>
			</x-table.tr>
		@endif
	@empty
		<x-table.tr class="h4rem d-flex align-items-center justify-content-center" noborder>
			<p class="color-gray-400">Нет данных</p>
		</x-table.tr>
	@endforelse
@endif