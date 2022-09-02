<x-input-group group="normal">
	
	<form id="contractForm">
		<div class="row g-30">
			<div class="col-5">
				<div class="form">
					<div class="form__item pb3rem mb1px">
						<label class="form__label color-dark fz16px">Номер объекта</label>
						@if($guard == 'site')
							<strong class="fz14px d-block mt4px">{{$new_object_number ?? $object_number ?? null}}</strong>
							@isset($new_object_number)
								<input type="hidden" name="object_number" value="{{$new_object_number}}">
							@endisset
						@elseif($guard == 'admin')
							<x-input
								id="objectNumber"
								name="object_number"
								type="number"
								value="{{$new_object_number ?? $object_number ?? null}}"
								class="w10rem"
								placeholder="00000"
								showrows
								/>
								<input
									id="hiddenObjectNumber"
									type="hidden"
									name="object_number"
									value="{{$new_object_number ?? $object_number ?? null}}"
									>
						@endif
					</div>
					
					<div class="form__item">
						<label class="form__label color-dark">Название / заявитель</label>
						<x-input name="title" value="{{$title ?? null}}" class="w100" />
					</div>
					
					<div class="form__item">
						<label class="form__label color-dark">Титул</label>
						<x-textarea name="titul" value="{{$titul ?? null}}" class="w100" />
					</div>
					
					<div class="form__item">
						<label class="form__label color-dark">Номер договора</label>
						<x-input name="contract" value="{{$contract ?? null}}" class="w100" />
					</div>
					
				</div>
				
				<div class="form__item">
					<label class="form__label color-dark">Стоимость договора</label>
					<x-input name="price" value="{{$price ?? 0}}" icon="ruble-sign" class="w100" />
				</div>
			</div>
			
			<div class="col-5">
					
				
				<div class="form__item">
					<div class="row row-cols-2 g-10">
						<div class="col">
							<label class="form__label color-dark">Дата начала договора</label>
							<x-datepicker name="date_start" date="{{$date_start ?? null}}" calendarid="cntractForm{{$data['rand_id']}}" class="w100" />
						</div>
						<div class="col">
							<label class="form__label color-dark">Дата окончания договора</label>
							<x-datepicker name="date_end" date="{{$date_end ?? null}}" calendarid="cntractForm{{$data['rand_id']}}" class="w100" />
						</div>
					</div>	
				</div>
				
				<div class="form__item">
					<label class="form__label color-dark">Заказчик</label>
					<x-select :options="$data['customers']" name="customer" value="{{$customer ?? null}}" class="w100" />
				</div>
				
				<div class="form__item">
					{{-- <label class="form__label color-dark">Населенный пункт</label>
					<x-select :options="$data['locality']" name="locality" value="{{$locality ?? null}}" class="w100" /> --}}
					
					
					<label class="form__label color-dark">Населенный пункт</label>
					<x-input name="locality" value="{{$locality ?? null}}" class="w100" />
				</div>
				
				<div class="form__item">
					<label class="form__label color-dark">Исполнтель</label>
					<x-select :options="$data['contractors']" name="contractor" value="{{$contractor ?? null}}" class="w100" />
				</div>
				
				<div class="form__item">
					<label class="form__label color-dark">Тип договора</label>
					<x-select :options="$data['types']" name="type" value="{{$type ?? null}}" class="w100" />
				</div>
			</div>
			
			<div class="col-2">
				<div class="form__item">
					<x-checkbox name="subcontracting" :checked="$subcontracting ?? null" label="Субподряд" />
				</div>
				
				<div class="form__item">
					<x-checkbox name="hoz_method" :checked="$hoz_method ?? null" label="Хоз способ" />
				</div>
			</div>
		</div>
		
		
		
		
		@if($departments)
			<div class="mt2rem">
				
				<fieldset class="fieldset">
					<legend>Отделы и этапы</legend>
					<div class="row row-cols-{{count($departments) < 4 ? 4 : count($departments)}} g-15">
						@foreach($departments as $dept)
							<div class="col">
								<div @class([
									'h100',
									'd-flex',
									'flex-column',
									'ustify-content-between',
									'border-right border-light pr15px' => !$loop->last
									])
									depblock
									>
									<div class="d-flex align-items-center justify-content-between mb2rem">
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
											action="contractChooseAllSteps"
											title="Выбрать все / снять выделение"
											:disabled="count($dept->steps) == 0"
											><i class="fa-solid fa-check-double"></i></x-button>
									</div>
									
									@if(count($dept->steps))
										<ul class="mb2rem" stepslist>
											@foreach($dept->steps as $step)
												<li @class([
													'row',
													'align-items-start',
													'justify-content-between',
													'mb6px'	=> !$loop->last,
													'border-top border-light pt10px' => !$loop->first,
													])
													stepsrow>
													<div class="col">
														<x-checkbox
															name="departments[{{$dept['id']}}][steps][{{$step['id']}}][choosed]"
															:checked="$cd[$dept['id']]['steps'][$step['id']]['show'] ?? null"
															group="small"
															:label="$step['name']"
															action="chechStep:{{$step['type'] == 3 ? 1 : 0}}"
															tag="stepcheck"
															/>
														
														@if($step['type'] == 3)
															<x-select
																:options="$data['deps_users'][$dept['id']] ?? null"
																name="assigned[dep_{{$dept['id']}}][step_{{$step['id']}}]"
																:value="$deps_assigned_users[$dept['id']] ?? null"
																:enabled="$cd[$dept['id']]['steps'][$step['id']]['show'] ?? false"
																
																label="Ответственный"
																empty="Нет сотрудников"
																choose="Сотрудник не выбран"
																choose-empty
																empty-has-value
																tag="stepassignedselect"
																group="small"
																class="w100 mt8px"
																/>
														@endif
													</div>
													
													@if($step['type'] != 3)
														<div class="col-auto">
															<x-input
																name="departments[{{$dept['id']}}][steps][{{$step['id']}}][deadline]"
																group="small"
																label="Срок"
																type="number"
																showrows
																title="Дедлайн"
																:value="$cd[$dept['id']]['steps'][$step['id']]['deadline'] ?? $step['deadline']"
																:enabled="$cd[$dept['id']]['steps'][$step['id']]['show'] ?? false"
																placeholder="0"
																tag="stepdeadline"
																class="w5rem ml5px"
																/>
														</div>
													@endif
												</li>
											@endforeach
										</ul>
									@else
										<p class="color-light">Нет этапов</p>
									@endif	
									
									@if(count($dept->steps) || $dept['assigned_primary'])
										<div class="h8rem mt-auto border-top border-light d-flex flex-column justify-content-end">
											{{-- @if($dept['assigned_primary'] && count($dept->steps))
												<div>
													<x-select
														:options="$data['deps_users'][$dept['id']] ?? null"
														name="departments[{{$dept['id']}}][assigned]"
														:value="$deps_assigned_users[$dept['id']] ?? null"
														:disabled="empty($cd[$dept['id']]['steps'])"
														class="w100"
														label="Ответственный"
														empty="Нет сотрудников"
														choose="Сотрудник не выбран"
														choose-empty
														empty-has-value
														tag="assignedindepartment"
														/>
												</div>
											@endif --}}
											
											@if(count($dept->steps))
												<div class="pt15px">
													<x-checkbox
														name="departments[{{$dept['id']}}][show]"
														:checked="$cd[$dept['id']]['show'] ?? null"
														:disabled="empty($cd[$dept['id']]['steps'])"
														label="Отобразить в отделе"
														tag="showindepartment"
														/>
												</div>
											@endif
										</div>
									@endif
								</div>
							</div>
						@endforeach
					</div>
					
				</fieldset>
				
				{{-- <p class="mb1rem"><strong class="fz18px">Отделы и этапы</strong></p>
				<hr class="form__line mb1rem"> --}}
				
			</div>
		@endif
		
	</form>
</x-input-group>