/**
 * ddrDrawing Plugin
 * Простая реализация плагина для рисования на JointJS
 */

let graph = null
let paper = null
let currentTool = 'select'

/**
 * Инициализация плагина
 */
function init() {
	console.log('Initializing ddrDrawing')
	
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
	
	// События
	setupEvents()
	
	// Настройка стилей для интерактивности
	setupInteractiveStyles()
	
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

// Экспорт
const ddrDrawing = () => ({
	init,
	setTool,
	createRectangle,
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