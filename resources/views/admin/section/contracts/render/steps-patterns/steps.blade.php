@if($departments)	
	<fieldset class="fieldset" id="stepsPatternStepsBlock">
		<legend>Отделы и этапы</legend>
		<div class="row row-cols-{{count($departments) < 4 ? 4 : count($departments)}} g-15">
			@foreach($departments as $dept)
				<div class="col">
					<div
						@class([
							'h100',
							'd-flex',
							'flex-column',
							'ustify-content-between',
							'border-right border-light pr15px' => !$loop->last
						])
						depblock
						>
						<div class="d-flex align-items-center justify-content-between mb2rem minh3rem">
							<strong
								@class([
									'fz15px',
									'd-block',
									'mr1rem',
									'color-gray' => count($dept->steps) == 0,
								])
								>{{$dept['name']}}</strong>
							<x-button
								variant="yellow"
								group="verysmall"
								action="stepsPatternChooseAll"
								title="Выбрать все / снять выделение"
								:disabled="count($dept->steps) == 0"
								><i class="fa-solid fa-check-double"></i></x-button>
						</div>
						
						@if(count($dept->steps))
							<ul stepslist>
								@foreach($dept->steps as $step)
									<li @class([
										'd-flex',
										'align-items-start',
										'justify-content-between',
										'mb6px'	=> !$loop->last,
										'border-top border-light pt10px' => !$loop->first,
										])
										stepsrow>
										
										<x-checkbox
											{{-- name="departments[{{$dept['id']}}][steps][{{$step['id']}}]" --}}
											:checked="in_array($step['id'], $stepsdata[$dept['id']] ?? [])"
											group="small"
											:label="$step['name']"
											tag="stepcheck:{{$dept['id']}}|{{$step['id']}}"
											/>
									</li>
								@endforeach
							</ul>
						@else
							<p class="color-light">Нет этапов</p>
						@endif
					</div>
				</div>
			@endforeach
		</div>
		
	</fieldset>
@endif