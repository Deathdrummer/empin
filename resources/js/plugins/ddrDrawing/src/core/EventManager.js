import logger from './Logger.js'

/**
 * Менеджер событий для централизации обработки
 */
class EventManager {
	constructor(canvas) {
		this.canvas = canvas
		this.toolManager = null
		this.contextMenu = null
		this.isDragging = false
		this.dragStart = { x: 0, y: 0 }
		this.startTranslate = { tx: 0, ty: 0 }
		this.eventHandlers = new Map()
	}

	/**
	 * Установка менеджера инструментов
	 */
	setToolManager(toolManager) {
		this.toolManager = toolManager
	}

	/**
	 * Установка контекстного меню
	 */
	setContextMenu(contextMenu) {
		this.contextMenu = contextMenu
	}

	/**
	 * Инициализация событий
	 */
	init() {
		this._setupToolbarEvents()
		this._setupCanvasEvents()
		this._setupPaperEvents()
		this._setupGlobalEvents()
		logger.log('EventManager initialized')
	}

	/**
	 * События панели инструментов
	 */
	_setupToolbarEvents() {
		const toolHandler = (e) => {
			const btn = e.target.closest('.ddrdrawing__tool-btn')
			if (btn) {
				const tool = btn.getAttribute('data-tool')
				this._handleToolClick(tool)
			}
		}
		
		document.addEventListener('click', toolHandler)
		this.eventHandlers.set('toolbar', toolHandler)
	}

	/**
	 * События холста
	 */
	_setupCanvasEvents() {
		const paper = this.canvas.getPaper()
		
		// Масштабирование колесом мыши
		const wheelHandler = (evt) => {
			evt.preventDefault()
			this._handleWheel(evt)
		}
		paper.el.addEventListener('wheel', wheelHandler)
		
		// Клик по пустому месту
		paper.on('blank:pointerdown', (evt) => {
			this._handleCanvasPointerDown(evt)
		})
	}

	/**
	 * События элементов
	 */
	_setupPaperEvents() {
		const paper = this.canvas.getPaper()
		
		// События элементов
		paper.on('cell:mouseenter', (cellView) => {
			this._handleCellMouseEnter(cellView)
		})
		
		paper.on('cell:mouseleave', (cellView) => {
			this._handleCellMouseLeave(cellView)
		})
		
		paper.on('cell:pointerdown', (cellView, evt) => {
			this._handleCellPointerDown(cellView, evt)
		})
		
		paper.on('cell:contextmenu', (cellView, evt) => {
			this._handleCellContextMenu(cellView, evt)
		})
		
		// События портов для hover эффектов
		paper.on('element:magnet:pointerenter', (elementView, evt) => {
			this._handlePortMouseEnter(elementView, evt)
		})
		
		paper.on('element:magnet:pointerleave', (elementView, evt) => {
			this._handlePortMouseLeave(elementView, evt)
		})
		
		// События создания связей
		paper.on('link:connect', (linkView) => {
			this._handleLinkConnect(linkView)
		})
		
		paper.on('link:disconnect', (linkView) => {
			this._handleLinkDisconnect(linkView)
		})
		
		// Блокируем стандартное контекстное меню
		paper.el.addEventListener('contextmenu', (evt) => {
			evt.preventDefault()
		})
	}

	/**
	 * Глобальные события
	 */
	_setupGlobalEvents() {
		// Перемещение мыши
		const moveHandler = (evt) => {
			this._handlePointerMove(evt)
		}
		document.addEventListener('pointermove', moveHandler)
		
		// Завершение перетаскивания
		const upHandler = (evt) => {
			this._handlePointerUp(evt)
		}
		document.addEventListener('pointerup', upHandler)
		
		// Объединенный обработчик кликов
		const clickHandler = (e) => {
			this._handleGlobalClick(e)
		}
		document.addEventListener('click', clickHandler)
		
		// Изменение размера окна
		const resizeHandler = () => {
			this._handleWindowResize()
		}
		window.addEventListener('resize', resizeHandler)
		
		// Сохраняем обработчики для cleanup
		this.eventHandlers.set('move', moveHandler)
		this.eventHandlers.set('up', upHandler)
		this.eventHandlers.set('click', clickHandler)
		this.eventHandlers.set('resize', resizeHandler)
	}

	/**
	 * Обработка клика по инструменту
	 */
	_handleToolClick(tool) {
		logger.log('Tool clicked:', tool)
		
		// Специальные инструменты
		switch (tool) {
			case 'undo':
				this.emit('undo')
				this.toolManager.activateTool('select')
				break
			case 'redo':
				this.emit('redo')
				this.toolManager.activateTool('select')
				break
			case 'zoom-in':
				this.emit('zoom-in')
				this.toolManager.activateTool('select')
				break
			case 'zoom-out':
				this.emit('zoom-out')
				this.toolManager.activateTool('select')
				break
			case 'zoom-fit':
				this.emit('zoom-fit')
				this.toolManager.activateTool('select')
				break
			default:
				this.toolManager.activateTool(tool)
		}
	}

	/**
	 * Обработка колеса мыши
	 */
	_handleWheel(evt) {
		const currentScale = this.canvas.getScale()
		const scaleFactor = evt.deltaY > 0 ? 0.9 : 1.1
		const newScale = currentScale.sx * scaleFactor
		
		if (newScale > 0.1 && newScale < 5) {
			const rect = this.canvas.getPaper().el.getBoundingClientRect()
			const mouseX = evt.clientX - rect.left
			const mouseY = evt.clientY - rect.top
			
			const currentTransform = this.canvas.getMatrix()
			const currentTx = currentTransform.e
			const currentTy = currentTransform.f
			
			const localBeforeX = (mouseX - currentTx) / currentScale.sx
			const localBeforeY = (mouseY - currentTy) / currentScale.sy
			
			this.canvas.setScale(newScale)
			
			const newTx = mouseX - localBeforeX * newScale
			const newTy = mouseY - localBeforeY * newScale
			
			this.canvas.setTranslation(newTx, newTy)
		}
	}

	/**
	 * Обработка клика по холсту
	 */
	_handleCanvasPointerDown(evt) {
		const point = this.canvas.clientToLocalPoint(evt.clientX, evt.clientY)
		
		// Передаем событие текущему инструменту
		if (this.toolManager) {
			this.toolManager.handleCanvasClick(point, evt)
		}
		
		// Логика перетаскивания для select tool
		const currentTool = this.toolManager.getCurrentTool()
		if (currentTool && currentTool.name === 'select') {
			this.isDragging = true
			this.dragStart = { x: evt.clientX, y: evt.clientY }
			const currentTransform = this.canvas.getMatrix()
			this.startTranslate = { tx: currentTransform.e, ty: currentTransform.f }
			
			this.canvas.getPaper().el.style.cursor = 'grabbing'
		}
	}

	/**
	 * События элементов
	 */
	_handleCellMouseEnter(cellView) {
		if (!cellView.model.get('selected')) {
			cellView.model.attr('body/stroke', '#8b8b8b')
		}
	}

	_handleCellMouseLeave(cellView) {
		if (!cellView.model.get('selected')) {
			cellView.model.attr('body/stroke', '#cfcccc')
		}
	}

	_handleCellPointerDown(cellView, evt) {
		// Снимаем выделение со всех элементов
		this.canvas.getGraph().getCells().forEach(cell => {
			cell.set('selected', false)
			cell.attr('body/stroke', '#cfcccc')
		})
		
		// Выделяем текущий элемент
		cellView.model.set('selected', true)
		cellView.model.attr('body/stroke', '#00deff')
		
		// Передаем событие инструменту
		if (this.toolManager) {
			this.toolManager.handleElementClick(cellView.model, cellView)
		}
	}

	_handleCellContextMenu(cellView, evt) {
		evt.preventDefault()
		
		if (this.contextMenu) {
			const canvasContainer = document.querySelector('#ddrDrawingCanvas')
			const canvasContainerParent = canvasContainer.parentElement
			const containerRect = canvasContainerParent.getBoundingClientRect()
			const x = evt.clientX - containerRect.left
			const y = evt.clientY - containerRect.top
			
			this.contextMenu.show(x, y, cellView.model)
		}
	}

	/**
	 * Обработка движения мыши
	 */
	_handlePointerMove(evt) {
		const currentTool = this.toolManager ? this.toolManager.getCurrentTool() : null
		
		if (this.isDragging && currentTool && currentTool.name === 'select') {
			const deltaX = evt.clientX - this.dragStart.x
			const deltaY = evt.clientY - this.dragStart.y
			
			const newTranslateX = this.startTranslate.tx + deltaX
			const newTranslateY = this.startTranslate.ty + deltaY
			
			this.canvas.setTranslation(newTranslateX, newTranslateY)
		}
	}

	/**
	 * Завершение перетаскивания
	 */
	_handlePointerUp() {
		if (this.isDragging) {
			this.isDragging = false
			this.canvas.getPaper().el.style.cursor = 'default'
		}
	}

	/**
	 * Глобальный обработчик кликов
	 */
	_handleGlobalClick(e) {
		// Обработка переключения вкладок
		const tabItem = e.target.closest('[ddrtabsitem]')
		if (tabItem) {
			const tabId = tabItem.getAttribute('ddrtabsitem')
			if (tabId === 'systemTab7') {
				setTimeout(() => this.canvas.updatePaperSize(), 100)
			}
		}
		
		// Скрытие контекстного меню
		if (!e.target.closest('.ddrdrawing__context-menu') && this.contextMenu) {
			this.contextMenu.hide()
		}
	}

	/**
	 * Обработка изменения размера окна
	 */
	_handleWindowResize() {
		const drawingTab = document.querySelector('[ddrtabscontentitem="systemTab7"]')
		if (drawingTab && drawingTab.classList.contains('ddrtabscontent__item_visible')) {
			setTimeout(() => this.canvas.updatePaperSize(), 100)
		}
	}

	/**
	 * Обработка наведения на порт
	 */
	_handlePortMouseEnter(elementView, evt) {
		const magnet = evt.target
		const portId = magnet.getAttribute('port')
		
		logger.log('Port hover enter:', portId)
		
		// Подсвечиваем порт
		magnet.setAttribute('fill', '#87d5ff')
		magnet.setAttribute('opacity', '1.0')
		magnet.setAttribute('r', '4')
		magnet.setAttribute('stroke-width', '2')
	}

	/**
	 * Обработка ухода курсора с порта
	 */
	_handlePortMouseLeave(elementView, evt) {
		const magnet = evt.target
		const portId = magnet.getAttribute('port')
		
		logger.log('Port hover leave:', portId)
		
		// Возвращаем обычный стиль
		magnet.setAttribute('fill', '#61cfff')
		magnet.setAttribute('opacity', '1.0')
		magnet.setAttribute('r', '3')
		magnet.setAttribute('stroke-width', '1')
	}

	/**
	 * Обработка создания связи
	 */
	_handleLinkConnect(linkView) {
		logger.log('=== Link connected ===', linkView.model.id)
		logger.log('Source:', linkView.model.source())
		logger.log('Target:', linkView.model.target())
		
		// Сохраняем информацию о занятых портах в модели элементов
		const source = linkView.model.source()
		const target = linkView.model.target()
		
		if (source.id && source.port) {
			const sourceElement = this.canvas.getGraph().getCell(source.id)
			if (sourceElement) {
				this._markPortAsHidden(sourceElement, source.port)
			}
		}
		
		if (target.id && target.port) {
			const targetElement = this.canvas.getGraph().getCell(target.id)
			if (targetElement) {
				this._markPortAsHidden(targetElement, target.port)
			}
		}
		
		// Скрываем занятые порты
		this._hideOccupiedPorts()
		
		// Можно добавить логику уведомлений или валидации
		this.emit('link-created', {
			link: linkView.model,
			source: linkView.model.source(),
			target: linkView.model.target()
		})
	}

	/**
	 * Обработка удаления связи
	 */
	_handleLinkDisconnect(linkView) {
		logger.log('=== Link disconnected ===', linkView.model.id)
		
		// Показываем освободившиеся порты
		this._hideOccupiedPorts()
		
		this.emit('link-removed', {
			link: linkView.model
		})
	}

	/**
	 * Помечаем порт как скрытый в модели элемента
	 */
	_markPortAsHidden(element, portId) {
		const hiddenPorts = element.get('hiddenPorts') || []
		if (!hiddenPorts.includes(portId)) {
			element.set('hiddenPorts', [...hiddenPorts, portId])
			logger.log('Marked port as hidden:', portId, 'for element:', element.id)
		}
	}

	/**
	 * Проверяем скрыт ли порт в модели элемента
	 */
	_isPortHidden(element, portId) {
		const hiddenPorts = element.get('hiddenPorts') || []
		return hiddenPorts.includes(portId)
	}

	/**
	 * Скрытие занятых портов
	 */
	_hideOccupiedPorts() {
		if (!this.canvas) return
		
		const graph = this.canvas.getGraph()
		const paper = this.canvas.getPaper()
		const links = graph.getLinks()
		
		// Собираем все занятые порты
		const occupiedPorts = new Set()
		links.forEach(link => {
			const source = link.source()
			const target = link.target()
			
			if (source.id && source.port) {
				occupiedPorts.add(`${source.id}:${source.port}`)
			}
			if (target.id && target.port) {
				occupiedPorts.add(`${target.id}:${target.port}`)
			}
		})
		
		logger.log('Occupied ports:', Array.from(occupiedPorts))
		
		// Проходим по всем элементам и скрываем/показываем порты
		graph.getElements().forEach(element => {
			const elementView = paper.findViewByModel(element)
			if (!elementView) return
			
			const ports = element.get('ports')
			if (!ports || !ports.items) return
			
			ports.items.forEach(port => {
				const portKey = `${element.id}:${port.id}`
				const portElement = elementView.el.querySelector(`[port="${port.id}"]`)
				
				if (portElement) {
					// Проверяем скрыт ли порт в модели элемента
					const isHiddenInModel = this._isPortHidden(element, port.id)
					
					if (occupiedPorts.has(portKey)) {
						// Скрываем занятый порт
						portElement.style.display = 'none'
						portElement.style.pointerEvents = 'none'
						portElement.setAttribute('magnet', 'false')
					} else if (isHiddenInModel) {
						// Порт помечен как скрытый в модели - не показываем
						portElement.style.display = 'none'
						portElement.style.pointerEvents = 'none'
						portElement.setAttribute('magnet', 'false')
					} else {
						// Показываем свободный порт
						portElement.style.display = 'block'
						portElement.setAttribute('fill', '#61cfff')
						portElement.setAttribute('stroke', '#0088ff')
						portElement.style.opacity = '1.0'
						portElement.style.pointerEvents = 'all'
						portElement.setAttribute('magnet', 'true')
					}
				}
			})
		})
	}

	/**
	 * Простая система событий
	 */
	emit(eventName, data) {
		const event = new CustomEvent(`ddr-drawing:${eventName}`, { detail: data })
		document.dispatchEvent(event)
	}

	/**
	 * Подписка на события
	 */
	on(eventName, callback) {
		document.addEventListener(`ddr-drawing:${eventName}`, callback)
	}

	/**
	 * Очистка событий
	 */
	destroy() {
		this.eventHandlers.forEach((handler, type) => {
			if (type === 'toolbar' || type === 'click') {
				document.removeEventListener('click', handler)
			} else if (type === 'move') {
				document.removeEventListener('pointermove', handler)
			} else if (type === 'up') {
				document.removeEventListener('pointerup', handler)
			} else if (type === 'resize') {
				window.removeEventListener('resize', handler)
			}
		})
		this.eventHandlers.clear()
	}
}

export default EventManager