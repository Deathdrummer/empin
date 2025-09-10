/**
 * Сервис для управления портами элементов
 */
class PortService {
	constructor(canvas, eventManager) {
		this.canvas = canvas
		this.eventManager = eventManager
		this.paper = null
		this.graph = null
		this.initialized = false
	}

	/**
	 * Инициализация сервиса
	 */
	init() {
		if (this.initialized) return

		this.paper = this.canvas.getPaper()
		this.graph = this.canvas.getGraph()

		// Подписываемся на события добавления портов
		this.eventManager.on('ports:add', (event) => {
			console.log('🎯 PortService received ports:add event:', event)
			const data = event.detail
			console.log('🎯 Event data:', data)
			this.addPortToElement(data.element, data.side)
		})

		// Обработчики для показа/скрытия портов
		this.setupPortVisibility()
		
		// Обработчики для создания связей
		this.setupLinkCreation()
		
		// Обработчики для выделения элементов
		this.setupSelectionHandlers()

		this.initialized = true
		console.log('PortService initialized')
	}

	/**
	 * Настройка обработчиков выделения
	 */
	setupSelectionHandlers() {
		// Показываем все порты когда элемент выделяется
		this.paper.on('cell:pointerdown', (cellView) => {
			if (cellView.model.isElement()) {
				console.log('🎯 Element selected - showing all ports')
				this.showAllPorts(cellView.model)
			}
		})

		// Скрываем порты когда кликают по пустому месту (снимается выделение)
		this.paper.on('blank:pointerdown', () => {
			console.log('🚫 Selection cleared - hiding all free ports')
			// Убираем selected класс со всех элементов
			this.paper.el.querySelectorAll('.joint-element.selected').forEach(el => {
				el.classList.remove('selected')
			})
			this.hideAllFreePorts()
		})

		// Также реагируем на события выделения от SelectTool
		this.eventManager.on('element:selected', (event) => {
			const element = event.detail.element
			console.log('📍 Element selected via SelectTool - showing all ports')
			this.showAllPorts(element)
		})

		this.eventManager.on('element:deselected', (event) => {
			const element = event.detail.element
			console.log('📍 Element deselected via SelectTool - hiding free ports')
			this.hideFreePorts(element)
		})
	}

	/**
	 * Показать только СВОБОДНЫЕ порты элемента при выделении
	 */
	showAllPorts(element) {
		// Сначала убираем selected класс с всех элементов
		this.paper.el.querySelectorAll('.joint-element.selected').forEach(el => {
			el.classList.remove('selected')
		})

		// Добавляем selected класс к текущему элементу
		const elementView = this.paper.findViewByModel(element)
		if (elementView) {
			elementView.el.classList.add('selected')
		}

		// Показываем только свободные порты
		this.showFreePorts(element)
	}

	/**
	 * Настройка видимости портов
	 */
	setupPortVisibility() {
		// Показываем порты при наведении на элемент
		this.paper.on('element:mouseenter', (elementView) => {
			console.log('🐭 element:mouseenter triggered for:', elementView.model.id)
			this.showFreePorts(elementView.model)
		})

		// Скрываем порты когда курсор покидает элемент
		this.paper.on('element:mouseleave', (elementView) => {
			console.log('🐭 element:mouseleave triggered for:', elementView.model.id)
			this.hideFreePorts(elementView.model)
		})

		// Также скрываем при перетаскивании
		this.paper.on('element:pointermove', (elementView) => {
			console.log('🐭 element:pointermove triggered for:', elementView.model.id)
			this.hideFreePorts(elementView.model)
		})
	}

	/**
	 * Показать свободные порты элемента
	 */
	showFreePorts(element) {
		console.log('🎯 showFreePorts called for element:', element.id)
		const ports = element.get('ports') || { items: [] }
		console.log('🎯 Element ports:', ports)
		const freePorts = this.getFreePorts(element)
		console.log('🎯 Free ports:', freePorts)

		freePorts.forEach(port => {
			console.log('🎯 Processing port:', port.id)
			// Находим группу порта, а не circle
			const portGroup = this.paper.findViewByModel(element)
				?.el?.querySelector(`[port="${port.id}"]`)?.closest('.joint-port')
			
			console.log('🎯 Found port group:', portGroup)
			if (portGroup) {
				console.log('🎯 Current classes before:', portGroup.className)
				portGroup.classList.add('port-visible')
				portGroup.classList.remove('port-hidden')
				console.log('🎯 Current classes after:', portGroup.className)
			}
		})
	}

	/**
	 * Скрыть свободные порты элемента
	 */
	hideFreePorts(element) {
		const ports = element.get('ports') || { items: [] }
		const freePorts = this.getFreePorts(element)

		freePorts.forEach(port => {
			// Находим группу порта, а не circle
			const portGroup = this.paper.findViewByModel(element)
				?.el?.querySelector(`[port="${port.id}"]`)?.closest('.joint-port')
			
			if (portGroup) {
				portGroup.classList.remove('port-visible')
				portGroup.classList.add('port-hidden')
			}
		})
	}

	/**
	 * Настройка обработчиков для создания связей
	 */
	setupLinkCreation() {
		// Показываем все свободные порты когда начинается создание связи
		this.paper.on('link:connect', (linkView, evt, elementViewConnected, magnet, arrowhead) => {
			console.log('Link creation started - showing all free ports')
			this.showAllFreePorts()
		})

		// Также показываем при старте перетаскивания от порта
		this.paper.on('element:magnet:pointerdown', (elementView, evt, magnet) => {
			console.log('Magnet drag started - showing all free ports')
			this.showAllFreePorts()
		})

		// Скрываем все порты когда связь создана или отменена
		this.paper.on('link:pointerup', () => {
			console.log('Link creation ended - hiding all ports')
			this.hideAllFreePorts()
		})

		// Скрываем при отмене создания связи
		this.paper.on('blank:pointerdown', () => {
			this.hideAllFreePorts()
		})
	}

	/**
	 * Показать все свободные порты на холсте
	 */
	showAllFreePorts() {
		// Добавляем CSS класс для активного режима создания связи
		this.paper.el.classList.add('ddr-linking-mode')
		
		this.graph.getElements().forEach(element => {
			this.showFreePorts(element)
		})
	}

	/**
	 * Скрыть все свободные порты на холсте
	 */
	hideAllFreePorts() {
		// Убираем CSS класс режима создания связи
		this.paper.el.classList.remove('ddr-linking-mode')
		
		this.graph.getElements().forEach(element => {
			this.hideFreePorts(element)
		})
	}

	/**
	 * Получить список свободных портов элемента
	 */
	getFreePorts(element) {
		const ports = element.get('ports') || { items: [] }
		const connectedPortIds = new Set()

		// Собираем ID всех подключенных портов
		this.graph.getLinks().forEach(link => {
			const source = link.get('source')
			const target = link.get('target')

			if (source.id === element.id && source.port) {
				connectedPortIds.add(source.port)
			}
			if (target.id === element.id && target.port) {
				connectedPortIds.add(target.port)
			}
		})

		// Возвращаем только свободные порты
		return ports.items.filter(port => !connectedPortIds.has(port.id))
	}

	/**
	 * Добавить порт к элементу
	 */
	addPortToElement(element, position) {
		console.log('🔍 PortService.addPortToElement called:', { element, position })
		
		if (!element) {
			console.error('❌ Element is undefined!')
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

		// Создаем новый порт с правильной структурой для JointJS v4
		const newPort = {
			id: portId,
			group: position,
			markup: [{
				tagName: 'circle',
				selector: 'portBody',
				attributes: {
					'port': portId,
					'class': 'port-hidden' // Новый порт скрыт по умолчанию
				}
			}],
			attrs: {
				portBody: {
					fill: '#61cfff',
					stroke: '#0088ff',
					strokeWidth: 1,
					r: 3,
					magnet: true,
					cursor: 'crosshair'
				}
			}
		}

		// Добавляем порт к элементу
		const updatedPorts = {
			...currentPorts,
			items: [...currentPorts.items, newPort],
			groups: {
				...currentPorts.groups,
				[position]: {
					position: position,
					markup: [{
						tagName: 'circle',
						selector: 'portBody'
					}],
					attrs: {
						portBody: {
							fill: '#61cfff',
							stroke: '#0088ff',
							strokeWidth: 1,
							r: 3,
							magnet: true,
							cursor: 'crosshair'
						}
					}
				}
			}
		}

		element.set('ports', updatedPorts)
		console.log(`Port ${portId} added to element ${element.id}`)

		// Скрываем все остальные свободные порты этого элемента
		this.hideFreePorts(element)
		
		// Показываем новый порт с плавной анимацией
		setTimeout(() => {
			// Находим группу порта, а не circle
			const newPortGroup = this.paper.findViewByModel(element)
				?.el?.querySelector(`[port="${portId}"]`)?.closest('.joint-port')
			
			if (newPortGroup) {
				console.log(`🔍 Found port GROUP:`, newPortGroup)
				console.log(`🔍 Current classes:`, newPortGroup.className)
				
				newPortGroup.classList.remove('port-hidden')
				newPortGroup.classList.add('port-visible')
				console.log(`✨ Port ${portId} показан, classes after:`, newPortGroup.className)
				
				// Автоматически скрываем через 300ms
				setTimeout(() => {
					if (this.getFreePorts(element).some(port => port.id === portId)) {
						newPortGroup.classList.remove('port-visible')
						newPortGroup.classList.add('port-hidden')
						console.log(`🌙 Port ${portId} скрыт, classes after:`, newPortGroup.className)
					}
				}, 300)
			}
		}, 100)

		// Эмитим событие о добавлении порта
		this.eventManager.emit('ports:port-added', { element, side: position, portId })
	}

	/**
	 * Удалить порт из элемента
	 */
	removePortFromElement(element, portId) {
		const currentPorts = element.get('ports') || { items: [] }
		const updatedItems = currentPorts.items.filter(port => port.id !== portId)

		const updatedPorts = {
			...currentPorts,
			items: updatedItems
		}

		element.set('ports', updatedPorts)
		console.log(`Port ${portId} removed from element ${element.id}`)

		// Эмитим событие об удалении порта
		this.eventManager.emit('ports:port-removed', { element, portId })
	}

	/**
	 * Получить все порты элемента
	 */
	getElementPorts(element) {
		const ports = element.get('ports') || { items: [] }
		return ports.items
	}

	/**
	 * Проверить, есть ли у элемента порты
	 */
	hasFreePorts(element) {
		return this.getFreePorts(element).length > 0
	}

	/**
	 * Очистка сервиса
	 */
	destroy() {
		if (this.paper) {
			// Обычные события видимости портов
			this.paper.off('element:mouseenter')
			this.paper.off('element:mouseleave')
			this.paper.off('element:pointermove')
			
			// События создания связей
			this.paper.off('link:connect')
			this.paper.off('element:magnet:pointerdown')
			this.paper.off('link:pointerup')
			this.paper.off('blank:pointerdown')
			
			// События выделения
			this.paper.off('cell:pointerdown')
			
			// Убираем CSS класс если остался
			this.paper.el.classList.remove('ddr-linking-mode')
		}

		this.initialized = false
		console.log('PortService destroyed')
	}
}

export default PortService