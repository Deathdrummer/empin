<ul class="searchresutls__list scrollblock scrollblock-light">
	<li ddr-for="contract in search">
		<table class="searchresutls__table" ddr-click="chooseSearchedContract:contract,teamId">
			<tr>
				<td><strong class="fz10px">{{contract.object_number}}</strong></td>
				<td><p class="fz10px">{{contract.title}}</p></td>
				<td>
					<div class="h4rem fz10px scrollblock scrollblock-hidescroll">{{contract.titul}}</div>
				</td>
			</tr>
		</table>
	</li>
	<li ddr-if="!search.length" class="empty"><p>нет данных</p></li>
</ul>