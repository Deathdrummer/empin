<section>
	<x-card
		id="testCard"
		>
		
		
		
		{{-- <x-table>
			<x-table.head>
				<x-table.tr>
					<x-table.td class="w30rem"><p>thead 1</p></x-table.td>
					<x-table.td><p>thead 2</p></x-table.td>
					<x-table.td><p>thead 3</p></x-table.td>
				</x-table.tr>
			</x-table.head>
			<x-table.body>
				@for ($i = 0; $i < 1000; $i++)
				<x-table.tr>
					<x-table.td><p>body 1</p></x-table.td>
					<x-table.td><p>body 2</p></x-table.td>
					<x-table.td><p>body 3</p></x-table.td>
				</x-table.tr>
				@endfor
			</x-table.body>
			<x-table.foot></x-table.foot>
		</x-table> --}}
		
		
		
		
		
		<x-horisontal space="2rem" scroll="false">
			<x-horisontal.item class="h100">
				<div class="div-table">
					<div class="div-table-row">
						<div class="div-table-col" align="center"><p>тест 1</p></div>
						<div  class="div-table-col"><p>тест 2</p></div>
						<div  class="div-table-col"><p>тест 3</p></div>
					</div>
					<div class="div-table-row">
						<div class="div-table-col"><p>тест 4</p></div>
						<div class="div-table-col"><p>тест 5</p></div>
						<div class="div-table-col"><p>тест 6</p></div>
					</div>
					<div class="div-table-row">
						<div class="div-table-col"><p>тест 7</p></div>
						<div class="div-table-col"><p>тест 8</p></div>
						<div class="div-table-col"><p>тест 9</p></div>
					</div>
					<div class="div-table-row">
						<div class="div-table-col"><p>тест 10</p></div>
						<div class="div-table-col"><p>тест 11</p></div>
						<div class="div-table-col"><p>тест 12</p></div>
					</div>
			  </div>
			</x-horisontal.item>
			
			<x-horisontal.item class="h100">
				<div class="div-table">
					<div class="div-table-row">
						<div class="div-table-col" align="center"><p>тест 1</p></div>
						<div  class="div-table-col"><p>тест 2</p></div>
						<div  class="div-table-col"><p>тест 3</p></div>
					</div>
					<div class="div-table-row">
						<div class="div-table-col"><p>тест 4</p></div>
						<div class="div-table-col"><p>тест 5</p></div>
						<div class="div-table-col"><p>тест 6</p></div>
					</div>
					<div class="div-table-row">
						<div class="div-table-col"><p>тест 7</p></div>
						<div class="div-table-col"><p>тест 8</p></div>
						<div class="div-table-col"><p>тест 9</p></div>
					</div>
					<div class="div-table-row">
						<div class="div-table-col"><p>тест 10</p></div>
						<div class="div-table-col"><p>тест 11</p></div>
						<div class="div-table-col"><p>тест 12</p></div>
					</div>
			  </div>
			</x-horisontal.item>
			
			<x-horisontal.item class="h100">
				<div class="div-table">
					<div class="div-table-row">
						<div class="div-table-col" align="center"><p>тест 1</p></div>
						<div  class="div-table-col"><p>тест 2</p></div>
						<div  class="div-table-col"><p>тест 3</p></div>
					</div>
					<div class="div-table-row">
						<div class="div-table-col"><p>тест 4</p></div>
						<div class="div-table-col"><p>тест 5</p></div>
						<div class="div-table-col"><p>тест 6</p></div>
					</div>
					<div class="div-table-row">
						<div class="div-table-col"><p>тест 7</p></div>
						<div class="div-table-col"><p>тест 8</p></div>
						<div class="div-table-col"><p>тест 9</p></div>
					</div>
					<div class="div-table-row">
						<div class="div-table-col"><p>тест 10</p></div>
						<div class="div-table-col"><p>тест 11</p></div>
						<div class="div-table-col"><p>тест 12</p></div>
					</div>
			  </div>
			</x-horisontal.item>
			
			<x-horisontal.item class="h100">
				<div class="div-table">
					<div class="div-table-row">
						<div class="div-table-col" align="center"><p>тест 1</p></div>
						<div  class="div-table-col"><p>тест 2</p></div>
						<div  class="div-table-col"><p>тест 3</p></div>
					</div>
					<div class="div-table-row">
						<div class="div-table-col"><p>тест 4</p></div>
						<div class="div-table-col"><p>тест 5</p></div>
						<div class="div-table-col"><p>тест 6</p></div>
					</div>
					<div class="div-table-row">
						<div class="div-table-col"><p>тест 7</p></div>
						<div class="div-table-col"><p>тест 8</p></div>
						<div class="div-table-col"><p>тест 9</p></div>
					</div>
					<div class="div-table-row">
						<div class="div-table-col"><p>тест 10</p></div>
						<div class="div-table-col"><p>тест 11</p></div>
						<div class="div-table-col"><p>тест 12</p></div>
					</div>
			  </div>
			</x-horisontal.item>
		</x-horisontal>
	
		
		
		
		
	
	
	</x-card>
</section>



<style>
.div-table {
	display: table;         
	width: auto;         
	background-color: #eee;         
	border: 1px solid #666666;         
	border-spacing: 5px; /* cellspacing:poor IE support for  this */
}

.div-table-row {
	display: table-row;
	width: auto;
	clear: both;
}

.div-table-col {
	float: left; /* fix for  buggy browsers */
	display: table-column;         
	width: 300px;         
	background-color: #ccc;  
}
</style>
