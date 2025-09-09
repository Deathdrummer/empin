/**
 * ddrDrawing Plugin
 * Простая реализация плагина для рисования на JointJS
 */

let graph = null
let paper = null
let currentTool = 'select'
let contextMenu = null


/**
 * Инициализация плагина
 */
function init() {
	console.log('=== OLD ddrDrawing initializing ===')
	console.log('File path: index-old.js (LEGACY CODE)')
	
	// Проверяем JointJS
	if (!window.joint) {
		console.error('JointJS not found')
		return
	}
	
	// Находим контейнер
	const container = document.querySelector('#ddrDrawingCanvas')
	if (!container) {
		console.error('Canvas container not found')
		return
	}
	
	console.log('Creating graph and paper')
	
	// Создаем граф
	graph = new window.joint.dia.Graph()
	
	// Создаем paper
	paper = new window.joint.dia.Paper({
		el: container,
		model: graph,
		width: '100%',
		height: '100%',
		gridSize: 10,
		drawGrid: {
			name: 'dot',
			args: {
				color: '#cccccc',
				thickness: 1
			}
		},
		background: {
			color: '#fdfdfd'
		}
	})
	
	// Обновляем размеры через небольшой таймаут для правильной инициализации
	setTimeout(updatePaperSize, 50)
	
	// События
	setupEvents()
	
	// Настройка стилей для интерактивности
	setupInteractiveStyles()
	
	// Создание контекстного меню
	createContextMenu()
	
	// Обработка событий контекстного меню
	setupContextMenuEvents()
	
	// Обработчик переключения вкладок для обновления размеров
	setupTabSwitchHandler()
	
	console.log('ddrDrawing initialized')
}

/**
 * Настройка событий
 */
function setupEvents() {
	console.log('Setting up events')
	
	// События кнопок
	document.addEventListener('click', (e) => {
		const btn = e.target.closest('.ddrdrawing__tool-btn')
		if (btn) {
			const tool = btn.getAttribute('data-tool')
			console.log('Button clicked:', tool)
			setTool(tool)
		}
	})
	
	// Масштабирование колесом мыши
	paper.el.addEventListener('wheel', (evt) => {
		evt.preventDefault()
		
		const currentScale = paper.scale()
		const scaleFactor = evt.deltaY > 0 ? 0.9 : 1.1
		const newScale = currentScale.sx * scaleFactor
		
		// Ограничиваем масштаб
		if (newScale > 0.1 && newScale < 5) {
			// Получаем позицию курсора относительно холста
			const rect = paper.el.getBoundingClientRect()
			const mouseX = evt.clientX - rect.left
			const mouseY = evt.clientY - rect.top
			
			// Получаем текущую трансформацию
			const currentTransform = paper.matrix()
			const currentTx = currentTransform.e
			const currentTy = currentTransform.f
			
			// Вычисляем точку в локальных координатах до масштабирования
			const localBeforeX = (mouseX - currentTx) / currentScale.sx
			const localBeforeY = (mouseY - currentTy) / currentScale.sy
			
			// Применяем новый масштаб
			paper.scale(newScale, newScale)
			
			// Вычисляем новую позицию трансляции, чтобы курсор остался на месте
			const newTx = mouseX - localBeforeX * newScale
			const newTy = mouseY - localBeforeY * newScale
			
			// Применяем трансляцию
			paper.translate(newTx, newTy)
		}
	})
	
	// Переменные для перетаскивания
	let isDragging = false
	let dragStart = { x: 0, y: 0 }
	let startTranslate = { tx: 0, ty: 0 }
	
	// Перемещение холста
	paper.on('blank:pointerdown', (evt) => {
		console.log('Canvas clicked, current tool:', currentTool)
		
		if (currentTool === 'rectangle') {
			const point = paper.clientToLocalPoint(evt.clientX, evt.clientY)
			console.log('Creating rectangle at:', point)
			createRectangle(point.x, point.y)
			setTool('select')
		} else if (currentTool === 'select') {
			// Начинаем перетаскивание
			isDragging = true
			dragStart = { x: evt.clientX, y: evt.clientY }
			const currentTransform = paper.matrix()
			startTranslate = { tx: currentTransform.e, ty: currentTransform.f }
			
			paper.el.style.cursor = 'grabbing'
		}
	})
	
	// Обработка перемещения мыши
	document.addEventListener('pointermove', (evt) => {
		if (isDragging && currentTool === 'select') {
			const deltaX = evt.clientX - dragStart.x
			const deltaY = evt.clientY - dragStart.y
			
			const newTranslateX = startTranslate.tx + deltaX
			const newTranslateY = startTranslate.ty + deltaY
			
			paper.translate(newTranslateX, newTranslateY)
		}
	})
	
	// Завершение перетаскивания
	document.addEventListener('pointerup', () => {
		if (isDragging) {
			isDragging = false
			paper.el.style.cursor = 'default'
		}
	})
	
	// Объединенный обработчик кликов
	document.addEventListener('click', (e) => {
		// Обработка переключения вкладок
		const tabItem = e.target.closest('[ddrtabsitem]')
		if (tabItem) {
			const tabId = tabItem.getAttribute('ddrtabsitem')
			if (tabId === 'systemTab7') {
				console.log('Drawing tab activated, updating paper size...')
				setTimeout(updatePaperSize, 100)
			}
		}
		
		// Скрытие контекстного меню при клике по пустому месту
		if (!e.target.closest('.ddrdrawing__context-menu')) {
			hideContextMenu()
		}
	})
}

/**
 * Создание контекстного меню
 */
function createContextMenu() {
	contextMenu = document.createElement('div')
	contextMenu.className = 'ddrdrawing__context-menu'
	contextMenu.innerHTML = `
		<div class="ddrdrawing__context-menu-item ddrdrawing__context-menu-parent" data-action="add-port">
			<span>Добавить порт</span>
			<i class="fa-solid fa-chevron-right"></i>
			<div class="ddrdrawing__context-menu-submenu">
				<div class="ddrdrawing__context-menu-item" data-action="add-port-top">
					<span>сверху</span>
				</div>
				<div class="ddrdrawing__context-menu-item" data-action="add-port-right">
					<span>справа</span>
				</div>
				<div class="ddrdrawing__context-menu-item" data-action="add-port-bottom">
					<span>снизу</span>
				</div>
				<div class="ddrdrawing__context-menu-item" data-action="add-port-left">
					<span>слева</span>
				</div>
			</div>
		</div>
	`
	
	// Добавляем в контейнер canvas-container для правильного позиционирования
	const canvasContainer = document.querySelector('#ddrDrawingCanvas')
	const canvasContainerParent = canvasContainer ? canvasContainer.parentElement : null
	
	if (canvasContainerParent && canvasContainerParent.classList.contains('ddrdrawing__canvas-container')) {
		canvasContainerParent.appendChild(contextMenu)
		console.log('Context menu created and added to canvas-container')
	} else {
		console.error('Canvas container parent not found for context menu')
		// Fallback - добавляем в canvas контейнер
		if (canvasContainer) {
			canvasContainer.appendChild(contextMenu)
			console.log('Context menu created and added to canvas container as fallback')
		}
	}
}

/**
 * Настройка событий контекстного меню
 */
function setupContextMenuEvents() {
	if (!contextMenu) {
		console.error('Context menu not found in setupContextMenuEvents')
		return
	}
	
	console.log('Setting up context menu events')
	
	// Обработка кликов по пунктам меню
	contextMenu.addEventListener('click', (e) => {
		console.log('Context menu clicked', e.target)
		const menuItem = e.target.closest('.ddrdrawing__context-menu-item')
		if (!menuItem) {
			console.log('No menu item found')
			return
		}
		
		const action = menuItem.getAttribute('data-action')
		const targetElementId = contextMenu.getAttribute('data-target-element')
		
		console.log('Menu action:', action, 'Target element:', targetElementId)
		
		// Находим элемент, на который кликнули правой кнопкой
		const targetElement = targetElementId ? graph.getCell(targetElementId) : null
		
		if (targetElement && action) {
			switch (action) {
				case 'add-port-top':
					addPort(targetElement, 'top')
					break
				case 'add-port-right':
					addPort(targetElement, 'right')
					break
				case 'add-port-bottom':
					addPort(targetElement, 'bottom')
					break
				case 'add-port-left':
					addPort(targetElement, 'left')
					break
			}
		}
		
		// Скрываем меню после клика
		hideContextMenu()
		e.stopPropagation()
	})
}

/**
 * Показать контекстное меню
 */
function showContextMenu(x, y, element) {
	if (!contextMenu) {
		console.error('Context menu element not found')
		return
	}
	
	console.log('Showing context menu at', x, y, 'for element', element ? element.id : 'none')
	
	contextMenu.style.left = x + 'px'
	contextMenu.style.top = y + 'px'
	contextMenu.style.display = 'block'
	contextMenu.style.position = 'absolute'
	contextMenu.style.zIndex = '9999'
	contextMenu.style.backgroundColor = 'white'
	contextMenu.style.border = '1px solid #ccc'
	contextMenu.setAttribute('data-target-element', element ? element.id : '')
	
	console.log('Context menu styles applied:', {
		display: contextMenu.style.display,
		left: contextMenu.style.left,
		top: contextMenu.style.top,
		zIndex: contextMenu.style.zIndex,
		position: contextMenu.style.position
	})
}

/**
 * Скрыть контекстное меню
 */
function hideContextMenu() {
	if (contextMenu) {
		contextMenu.style.display = 'none'
	}
}

/**
 * Настройка обработчика переключения вкладок
 */
function setupTabSwitchHandler() {
	// Обрабатываем событие изменения размера окна
	window.addEventListener('resize', () => {
		// Проверяем, видима ли вкладка с чертежами
		const drawingTab = document.querySelector('[ddrtabscontentitem="systemTab7"]')
		if (drawingTab && drawingTab.classList.contains('ddrtabscontent__item_visible')) {
			setTimeout(updatePaperSize, 100)
		}
	})
}

/**
 * Настройка интерактивных стилей для элементов
 */
function setupInteractiveStyles() {
	// События для изменения стилей при взаимодействии
	paper.on('cell:mouseenter', function(cellView) {
		// При наведении - серый цвет (только если не выделен)
		if (!cellView.model.get('selected')) {
			cellView.model.attr('body/stroke', '#8b8b8b')
		}
	})
	
	paper.on('cell:mouseleave', function(cellView) {
		// Возвращаем исходный цвет, если элемент не выделен
		if (!cellView.model.get('selected')) {
			cellView.model.attr('body/stroke', '#cfcccc')
		}
	})
	
	// События выделения/снятия выделения
	paper.on('cell:pointerdown', function(cellView) {
		// Снимаем выделение со всех элементов
		graph.getCells().forEach(cell => {
			cell.set('selected', false)
			cell.attr('body/stroke', '#cfcccc')
		})
		
		// Выделяем текущий элемент
		cellView.model.set('selected', true)
		cellView.model.attr('body/stroke', '#00deff')
	})
	
	// Контекстное меню по правому клику
	paper.on('cell:contextmenu', function(cellView, evt) {
		evt.preventDefault()
		console.log('Right click on cell')
		
		// Получаем координаты относительно canvas-container
		const canvasContainer = document.querySelector('#ddrDrawingCanvas')
		const canvasContainerParent = canvasContainer.parentElement
		const containerRect = canvasContainerParent.getBoundingClientRect()
		const x = evt.clientX - containerRect.left
		const y = evt.clientY - containerRect.top
		
		showContextMenu(x, y, cellView.model)
	})
	
	// Блокируем стандартное контекстное меню браузера на холсте
	paper.el.addEventListener('contextmenu', function(evt) {
		evt.preventDefault()
	})
	
	// Снятие выделения при клике по пустому месту
	paper.on('blank:pointerdown', function(evt) {
		// Проверяем, что это не создание фигуры
		if (currentTool === 'select') {
			graph.getCells().forEach(cell => {
				cell.set('selected', false)
				cell.attr('body/stroke', '#cfcccc')
			})
		}
	})
}

/**
 * Установка инструмента
 */
function setTool(tool) {
	console.log('Setting tool:', tool)
	
	// Убираем активный класс
	document.querySelectorAll('.ddrdrawing__tool-btn').forEach(btn => {
		btn.classList.remove('active')
	})
	
	// Добавляем активный класс
	const btn = document.querySelector(`[data-tool="${tool}"]`)
	if (btn) {
		btn.classList.add('active')
	}
	
	currentTool = tool
	
	// Обработка инструментов
	switch (tool) {
		case 'undo':
			undo()
			setTool('select')
			break
		case 'redo':
			redo()
			setTool('select')
			break
		case 'zoom-in':
			zoomIn()
			setTool('select')
			break
		case 'zoom-out':
			zoomOut()
			setTool('select')
			break
		case 'zoom-fit':
			zoomToFit()
			setTool('select')
			break
	}
}

/**
 * Создание прямоугольника
 */
function createRectangle(x, y) {
	// Привязываем к сетке (gridSize = 10)
	const gridSize = 10
	const snapX = Math.round(x / gridSize) * gridSize
	const snapY = Math.round(y / gridSize) * gridSize
	
	const rect = new window.joint.shapes.standard.Rectangle({
		position: { x: snapX - 10, y: snapY - 10 },
		size: { width: 20, height: 20 },
		attrs: {
			body: {
				fill: '#ffffff',
				stroke: '#cfcccc',
				strokeWidth: 1
			}
		}
	})
	
	graph.addCell(rect)
	console.log('Rectangle created at grid position:', snapX, snapY)
}

/**
 * Добавление порта к элементу
 */
function addPort(element, position) {
	if (!element) {
		console.error('Element not found for adding port')
		return
	}
	
	console.log('Adding port to element:', element.id, 'position:', position)
	
	// Получаем текущие порты элемента
	const currentPorts = element.get('ports') || { items: [] }
	
	// Определяем следующий ID порта для данной позиции
	const existingPortsOfPosition = currentPorts.items.filter(port => 
		port.group === position
	)
	const portNumber = existingPortsOfPosition.length + 1
	const portId = `${position}${portNumber}`
	
	// Создаем новый порт
	const newPort = {
		id: portId,
		group: position,
		attrs: {
			circle: {
				fill: '#4CAF50',
				stroke: '#2E7D32',
				strokeWidth: 1,
				r: 4
			}
		}
	}
	
	// Добавляем порт к элементу
	const updatedPorts = {
		...currentPorts,
		items: [...currentPorts.items, newPort],
		groups: {
			...currentPorts.groups,
			[position]: getPortGroupConfig(position)
		}
	}
	
	element.set('ports', updatedPorts)
	console.log('Port added:', portId)
}

/**
 * Получение конфигурации группы портов для позиции
 */
function getPortGroupConfig(position) {
	const configs = {
		top: {
			position: { name: 'top' },
			attrs: {
				circle: {
					fill: '#4CAF50',
					stroke: '#2E7D32',
					strokeWidth: 1,
					r: 4
				}
			}
		},
		right: {
			position: { name: 'right' },
			attrs: {
				circle: {
					fill: '#4CAF50',
					stroke: '#2E7D32',
					strokeWidth: 1,
					r: 4
				}
			}
		},
		bottom: {
			position: { name: 'bottom' },
			attrs: {
				circle: {
					fill: '#4CAF50',
					stroke: '#2E7D32',
					strokeWidth: 1,
					r: 4
				}
			}
		},
		left: {
			position: { name: 'left' },
			attrs: {
				circle: {
					fill: '#4CAF50',
					stroke: '#2E7D32',
					strokeWidth: 1,
					r: 4
				}
			}
		}
	}
	
	return configs[position] || configs.top
}

/**
 * Отмена
 */
function undo() {
	console.log('Undo')
}

/**
 * Повтор
 */
function redo() {
	console.log('Redo')
}

/**
 * Увеличение масштаба
 */
function zoomIn() {
	if (paper) {
		const scale = paper.scale()
		paper.scale(scale.sx * 1.2, scale.sy * 1.2)
	}
}

/**
 * Уменьшение масштаба
 */
function zoomOut() {
	if (paper) {
		const scale = paper.scale()
		paper.scale(scale.sx * 0.8, scale.sy * 0.8)
	}
}

/**
 * Подгонка по размеру (100%)
 */
function zoomToFit() {
	if (paper) {
		// Получаем размеры холста
		const paperRect = paper.el.getBoundingClientRect()
		const centerX = paperRect.width / 2
		const centerY = paperRect.height / 2
		
		// Получаем текущую трансформацию
		const currentTransform = paper.matrix()
		const currentScale = paper.scale()
		
		// Вычисляем текущий центр в локальных координатах
		const localCenterX = (centerX - currentTransform.e) / currentScale.sx
		const localCenterY = (centerY - currentTransform.f) / currentScale.sy
		
		// Устанавливаем масштаб 100%
		paper.scale(1, 1)
		
		// Вычисляем новую позицию трансляции, чтобы центр остался на месте
		const newTx = centerX - localCenterX * 1
		const newTy = centerY - localCenterY * 1
		
		// Применяем трансляцию
		paper.translate(newTx, newTy)
	}
}

/**
 * Обновление размеров paper
 */
function updatePaperSize() {
	if (!paper) return
	
	const container = paper.el
	if (!container || !container.offsetParent) {
		// Контейнер скрыт, повторяем попытку через 100ms
		setTimeout(updatePaperSize, 100)
		return
	}
	
	// Получаем фактические размеры контейнера
	const rect = container.getBoundingClientRect()
	const containerWidth = container.offsetWidth
	const containerHeight = container.offsetHeight
	
	console.log('Updating paper size:', containerWidth, 'x', containerHeight)
	
	// Обновляем размеры paper
	paper.setDimensions(containerWidth, containerHeight)
	
	// Принудительно обновляем отображение
	paper.render()
}

// Экспорт
const ddrDrawing = () => ({
	init,
	setTool,
	createRectangle,
	addPort,
	updatePaperSize,
	undo,
	redo,
	zoomIn,
	zoomOut,
	zoomToFit
})

// Глобальный доступ
if (typeof window !== 'undefined') {
	window.ddrDrawing = ddrDrawing
}