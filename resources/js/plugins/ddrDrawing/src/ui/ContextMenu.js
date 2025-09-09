/**
 * Контекстное меню
 */
class ContextMenu {
	constructor(canvas) {
		this.canvas = canvas
		this.element = null
		this.targetElement = null
		this.menuItems = new Map()
		this.isVisible = false
	}

	/**
	 * Инициализация
	 */
	init() {
		this.createElement()
		this.setupDefaultItems()
		this.setupEvents()
		console.log('ContextMenu initialized')
	}

	/**
	 * Создание DOM элемента меню
	 */
	createElement() {
		this.element = document.createElement('div')
		this.element.className = 'ddrdrawing__context-menu'
		
		// Добавляем в контейнер canvas-container для правильного позиционирования
		const canvasContainer = document.querySelector('#ddrDrawingCanvas')
		const canvasContainerParent = canvasContainer ? canvasContainer.parentElement : null
		
		if (canvasContainerParent && canvasContainerParent.classList.contains('ddrdrawing__canvas-container')) {
			canvasContainerParent.appendChild(this.element)
			console.log('Context menu created and added to canvas-container')
		} else {
			console.error('Canvas container parent not found for context menu')
		}
	}

	/**
	 * Настройка стандартных пунктов меню
	 */
	setupDefaultItems() {
		// Добавляем группу портов
		this.addMenuGroup('add-port', 'Добавить порт', [
			{ id: 'add-port-top', label: 'сверху', action: 'add-port', data: { position: 'top' } },
			{ id: 'add-port-right', label: 'справа', action: 'add-port', data: { position: 'right' } },
			{ id: 'add-port-bottom', label: 'снизу', action: 'add-port', data: { position: 'bottom' } },
			{ id: 'add-port-left', label: 'слева', action: 'add-port', data: { position: 'left' } }
		])
		
		this.render()
	}

	/**
	 * Добавление группы пунктов меню
	 */
	addMenuGroup(groupId, groupLabel, items) {
		this.menuItems.set(groupId, {
			type: 'group',
			label: groupLabel,
			items: items
		})
	}

	/**
	 * Добавление одиночного пункта меню
	 */
	addMenuItem(id, label, action, data = {}) {
		this.menuItems.set(id, {
			type: 'item',
			label: label,
			action: action,
			data: data
		})
	}

	/**
	 * Рендер меню
	 */
	render() {
		let html = ''
		
		for (const [key, menuItem] of this.menuItems) {
			if (menuItem.type === 'group') {
				html += this.renderGroup(key, menuItem)
			} else {
				html += this.renderItem(key, menuItem)
			}
		}
		
		this.element.innerHTML = html
	}

	/**
	 * Рендер группы
	 */
	renderGroup(groupId, group) {
		let submenuHtml = ''
		
		group.items.forEach(item => {
			submenuHtml += `
				<div class="ddrdrawing__context-menu-item" data-action="${item.action}" data-item-id="${item.id}" data-position="${item.data.position || ''}">
					<span>${item.label}</span>
				</div>
			`
		})
		
		return `
			<div class="ddrdrawing__context-menu-item ddrdrawing__context-menu-parent" data-group="${groupId}">
				<span>${group.label}</span>
				<i class="fa-solid fa-chevron-right"></i>
				<div class="ddrdrawing__context-menu-submenu">
					${submenuHtml}
				</div>
			</div>
		`
	}

	/**
	 * Рендер пункта
	 */
	renderItem(itemId, item) {
		return `
			<div class="ddrdrawing__context-menu-item" data-action="${item.action}" data-item-id="${itemId}">
				<span>${item.label}</span>
			</div>
		`
	}

	/**
	 * Настройка событий
	 */
	setupEvents() {
		this.element.addEventListener('click', (e) => {
			const menuItem = e.target.closest('.ddrdrawing__context-menu-item')
			if (!menuItem) return
			
			const action = menuItem.getAttribute('data-action')
			const itemId = menuItem.getAttribute('data-item-id')
			const position = menuItem.getAttribute('data-position')
			
			console.log('Context menu action:', action, 'Item:', itemId, 'Position:', position)
			
			if (action && this.targetElement) {
				this.handleAction(action, {
					element: this.targetElement,
					position: position,
					itemId: itemId
				})
			}
			
			this.hide()
			e.stopPropagation()
		})
	}

	/**
	 * Обработка действий меню
	 */
	handleAction(action, data) {
		switch (action) {
			case 'add-port':
				if (data.element && data.position) {
					this.addPort(data.element, data.position)
				}
				break
			default:
				console.warn('Unknown context menu action:', action)
		}
	}

	/**
	 * Добавление порта к элементу
	 */
	addPort(element, position) {
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
					'port': portId  // ВАЖНО: добавляем ID порта в DOM атрибуты
				}
			}],
			attrs: {
				portBody: {
					fill: '#61cfff',
					stroke: '#0088ff',
					strokeWidth: 1,
					r: 3,
					magnet: true,
					cursor: 'crosshair',
					// Базовый стиль для hover эффектов
					opacity: 0.8
				}
			}
		}
		
		// Добавляем порт к элементу
		const updatedPorts = {
			...currentPorts,
			items: [...currentPorts.items, newPort],
			groups: {
				...currentPorts.groups,
				[position]: this.getPortGroupConfig(position)
			}
		}
		
		element.set('ports', updatedPorts)
		console.log('Port added:', portId)
	}

	/**
	 * Получение конфигурации группы портов
	 */
	getPortGroupConfig(position) {
		const baseConfig = {
			attrs: {
				circle: {
					fill: '#4CAF50',
					stroke: '#2E7D32',
					strokeWidth: 1,
					r: 3
				}
			}
		}
		
		return {
			 position: {
				name: position,
				args: {
					// Настройки распределения:
					start: 0.3,    // отступ от начала (10%)
					end: 0.7,      // отступ от конца (90%)
					step: 10       // фиксированный шаг в px
				}
			},
			...baseConfig
		}
	}

	/**
	 * Показать меню
	 */
	show(x, y, element) {
		if (!this.element) {
			console.error('Context menu element not found')
			return
		}
		
		console.log('Showing context menu at', x, y, 'for element', element ? element.id : 'none')
		
		this.element.style.left = x + 'px'
		this.element.style.top = y + 'px'
		this.element.style.display = 'block'
		this.element.style.position = 'absolute'
		this.element.style.zIndex = '9999'
		this.element.style.backgroundColor = 'white'
		this.element.style.border = '1px solid #ccc'
		
		this.targetElement = element
		this.isVisible = true
	}

	/**
	 * Скрыть меню
	 */
	hide() {
		if (this.element) {
			this.element.style.display = 'none'
			this.isVisible = false
			this.targetElement = null
		}
	}

	/**
	 * Проверка видимости
	 */
	isMenuVisible() {
		return this.isVisible
	}

	/**
	 * Очистка
	 */
	destroy() {
		if (this.element && this.element.parentNode) {
			this.element.parentNode.removeChild(this.element)
		}
		this.menuItems.clear()
		this.targetElement = null
		this.isVisible = false
	}
}

export default ContextMenu