<section>
	<x-card
		id="permissionsCard"
		loading
		>
		
		<div class="ddrtabs">
			<div class="ddrtabs__nav ddrtabs__nav-auto w20rem">
				<ul class="ddrtabsnav" ddrtabsnav>
					<li
						class="ddrtabsnav__item ddrtabsnav__item_active"
						onclick="$.loadPermissionsSection(this, 'admin')"
						ddrtabsitem="testTab1"
						>Личный кабинет АДМИНИСТРАТОРОВ</li>
					<li
						class="ddrtabsnav__item"
						onclick="$.loadPermissionsSection(this, 'site')"
						ddrtabsitem="testTab2"
						>Личный кабинет СОТРУДНИКОВ</li>
				</ul>
			</div>
			
			<div class="ddrtabs__content ddrtabscontent" id="permissionsSectionsBlock"></div>
		</div>
	</x-card>
</section>


<script type="module">
	
	loadPermissionSection('admin', true);
	
	$.loadPermissionsSection = (tab, guard) => {
		loadPermissionSection(guard);
	}
	
	
	
	$.permissionSectionReset = (btn, guard = null) => {
		if (!guard) return false;
		let permissionsCardWait = $('#permissionsCard').ddrWait({
			iconHeight: '50px',
			bgColor: '#fff9',
			text: 'Обновление разрешений...'
		});
		
		let formData = new FormData(document.getElementById('permissionsSectionsForm'));
		formData.append('guard', guard);
		axiosQuery('put', 'ajax/permissions/sections', formData, 'json').then(({data, error, status, headers}) => {
			$('#refreshSectionsPermissions').ddrInputs('disable');
			
			$.each(data, (k, index) => {
				$('#permissionsSectionsList').find('[index="'+index+'"]').html('<i class="fa-solid fa-check color-green fz20px"></i>');
			});	
			
			permissionsCardWait.destroy();
			$('#permissionsSectionsBlock').ddrInputs('state', 'clear');
		});
		
	}

	/*$.permissionsSectionSetGroup = (select, id, permission, title) => {
		console.log(select, id, permission, title);
	}*/
	
	
	
	
	function loadPermissionSection(guard = 'admin', first = false) {
		let waiting;
		if (!first) waiting = $('#permissionsCard').ddrWait();
		
		axiosQuery('post', 'ajax/permissions/sections', {
			views: 'admin.section.permissions.render.sections',
			guard
		}).then(({data, error, status, headers}) => {
			
			$('#permissionsSectionsBlock').html(data);
			
			if (first) $('#permissionsCard').card('ready');
			else waiting.destroy();
			
			$('#permissionsSectionsBlock').ddrInputs('change', function() {
				$('#refreshSectionsPermissions').ddrInputs('enable');
			});
			//$('#permissionsCard').card('enableButton');
		});
	}
	
	
	
</script>