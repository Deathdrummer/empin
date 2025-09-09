 
/**
 * PortService - Service for managing element ports and their states
 */
export class PortService {
	constructor(eventBus, stateStore, graphService) {
		this.eventBus = eventBus;
		this.stateStore = stateStore;
		this.graphService = graphService;
		this.initialized = false;
		this.debugMode = false;
		this.portCounter = 0;
		
		this.bindEventHandlers();
	}

	/**
	 * Initializes the port service
	 */
	init() {
		if (this.initialized) {
			console.warn('PortService: Already initialized');
			return;
		}

		this.syncAllPortStates();
		this.initialized = true;

		this.eventBus.emit('ports:service-initialized');
	}

	/**
	 * Binds service to external events
	 */
	bindEventHandlers() {
		this.eventBus.on('element:added', (event) => {
			this.initializeElementPorts(event.element);
			this.bindElementPortWatcher(event.element);
		});
		this.eventBus.on('element:removed', (event) => this.cleanupElementPorts(event.element));
		this.eventBus.on('link:connect', (event) => this.handleLinkConnect(event.link));
		this.eventBus.on('link:disconnect', (event) => this.handleLinkDisconnect(event.link));
		this.eventBus.on('ports:show', (event) => this.showElementPorts(event.element));
		this.eventBus.on('ports:hide', (event) => this.hideElementPorts(event.element));
		this.eventBus.on('ports:add', (event) => this.addPortToElement(event.element, event.side));
		this.eventBus.on('ports:remove', (event) => this.removePortFromElement(event.element, event.portId));
	}

	/**
	 * Привязывает отслеживание изменений портов к элементу
	 */
	bindElementPortWatcher(element) {
		// Отслеживаем изменения портов
		element.on('change:ports', () => {
			const elementId = element.id;
			const visiblePorts = this.stateStore.get('ports.visible');
			console.log(`🔄 change:ports событие для элемента ${elementId}`);
			
			// Если элемент не должен показывать порты - принудительно скрываем их
			if (!visiblePorts.has(elementId)) {
				console.log(`🚨 change:ports: Элемент ${elementId} НЕ в списке видимых - принудительно скрываем`);
				setTimeout(() => {
					console.log(`🔧 change:ports: Выполняем принудительное скрытие для ${elementId}`);
					this.forceHideAllElementPorts(element);
				}, 0);
			} else {
				console.log(`ℹ️ change:ports: Элемент ${elementId} в списке видимых - проверяем занятые порты`);
				setTimeout(() => {
					this.forceHideAllElementPorts(element);
				}, 0);
			}
		});
	}

	/**
	 * Creates empty port configuration for elements without ports
	 */
	createEmptyPortsConfig() {
		return {
			groups: {
				'default': {
					attrs: {
						circle: {
							r: 4,
							magnet: true,
							stroke: '#31d0c6',
							strokeWidth: 2,
							fill: '#ffffff',
							'fill-opacity': 0,
							'stroke-opacity': 0,
							opacity: 0,
							'pointer-events': 'none'
						}
					},
					markup: '<circle r="4" />'
				}
			},
			items: []
		};
	}

	/**
	 * Creates standard port configuration with basic ports
	 */
	createStandardPortsConfig() {
		return {
			groups: {
				'default': {
					attrs: {
						circle: {
							r: 4,
							magnet: true,
							stroke: '#31d0c6',
							strokeWidth: 2,
							fill: '#ffffff',
							'fill-opacity': 0,
							'stroke-opacity': 0,
							opacity: 0,
							'pointer-events': 'none'
						}
					},
					markup: '<circle r="4" />'
				}
			},
			items: [
				{ group: 'default', args: { x: '50%', y: '0%' }, id: 'top_0' },
				{ group: 'default', args: { x: '100%', y: '50%' }, id: 'right_0' },
				{ group: 'default', args: { x: '50%', y: '100%' }, id: 'bottom_0' },
				{ group: 'default', args: { x: '0%', y: '50%' }, id: 'left_0' }
			]
		};
	}

	/**
	 * Initializes port state for an element
	 */
	initializeElementPorts(element) {
		const elementId = element.id;
		const existingPorts = element.getPorts();
		const portStates = this.stateStore.get('ports.states');

		const elementPortState = {
			top: [],
			right: [],
			bottom: [],
			left: []
		};

		existingPorts.forEach(port => {
			const side = this.determinePortSide(port.id);
			if (side) {
				elementPortState[side].push({
					id: port.id,
					occupied: false,
					linkId: null,
					magnet: port.args ? port.args.magnet !== false : true
				});
			}
		});

		portStates.set(elementId, elementPortState);
		this.stateStore.set('ports.states', portStates);
		this.updatePortVisuals(element);

		this.eventBus.emit('ports:element-initialized', { element, portState: elementPortState });
	}

	/**
	 * Determines which side a port belongs to based on its ID
	 */
	determinePortSide(portId) {
		if (portId.startsWith('top_')) return 'top';
		if (portId.startsWith('right_')) return 'right';
		if (portId.startsWith('bottom_')) return 'bottom';
		if (portId.startsWith('left_')) return 'left';
		return null;
	}

	/**
	 * Adds a new port to an element on the specified side
	 */
	addPortToElement(element, side) {
		const elementId = element.id;
		const portStates = this.stateStore.get('ports.states');
		let elementPortState = portStates.get(elementId);

		if (!elementPortState) {
			this.initializeElementPorts(element);
			elementPortState = portStates.get(elementId);
		}

		const sideCount = elementPortState[side].length;
		const newPortId = `${side}_${sideCount}`;

		const portArgs = this.calculatePortPosition(side, sideCount);
		element.addPort({
			group: 'default',
			args: portArgs,
			id: newPortId
		});

		elementPortState[side].push({
			id: newPortId,
			occupied: false,
			linkId: null,
			magnet: true
		});

		console.log(`➕ addPortToElement: добавлен порт ${newPortId} к элементу ${elementId}`);
		
		this.redistributePortsOnSide(element, side);
		this.updatePortVisuals(element);

		// Принудительно скрываем все порты ПОСЛЕ того как JointJS закончит свои операции
		const visiblePorts = this.stateStore.get('ports.visible');
		console.log(`🔍 Элемент ${elementId} в списке видимых:`, visiblePorts.has(elementId));
		
		if (!visiblePorts.has(elementId)) {
			console.log(`⏱️ Запланировано принудительное скрытие для элемента ${elementId}`);
			setTimeout(() => {
				console.log(`🔧 Выполняем принудительное скрытие для элемента ${elementId}`);
				this.forceHideAllElementPorts(element);
			}, 0);
		}

		portStates.set(elementId, elementPortState);
		this.stateStore.set('ports.states', portStates);

		this.eventBus.emit('ports:port-added', { element, side, portId: newPortId });
		return newPortId;
	}

	/**
	 * Removes a port from an element
	 */
	removePortFromElement(element, portId) {
		const elementId = element.id;
		const portStates = this.stateStore.get('ports.states');
		const elementPortState = portStates.get(elementId);

		if (!elementPortState) return false;

		let removedPort = null;
		let portSide = null;

		for (const [side, ports] of Object.entries(elementPortState)) {
			const portIndex = ports.findIndex(p => p.id === portId);
			if (portIndex !== -1) {
				removedPort = ports[portIndex];
				portSide = side;

				if (removedPort.occupied && removedPort.linkId) {
					const link = this.graphService.getElementById(removedPort.linkId);
					if (link) {
						link.remove();
					}
				}

				ports.splice(portIndex, 1);
				break;
			}
		}

		if (!removedPort) return false;

		element.removePort(portId);
		this.redistributePortsOnSide(element, portSide);
		this.updatePortVisuals(element);

		portStates.set(elementId, elementPortState);
		this.stateStore.set('ports.states', portStates);

		this.eventBus.emit('ports:port-removed', { element, portId, side: portSide });
		return true;
	}

	/**
	 * Removes all ports from an element
	 */
	removeAllPorts(element) {
		const elementId = element.id;
		const portStates = this.stateStore.get('ports.states');
		const elementPortState = portStates.get(elementId);

		if (!elementPortState) return false;

		Object.values(elementPortState).flat().forEach(port => {
			if (port.occupied && port.linkId) {
				const link = this.graphService.getElementById(port.linkId);
				if (link) {
					link.remove();
				}
			}
		});

		const existingPorts = element.getPorts();
		existingPorts.forEach(port => {
			element.removePort(port.id);
		});

		portStates.set(elementId, {
			top: [],
			right: [],
			bottom: [],
			left: []
		});
		this.stateStore.set('ports.states', portStates);

		this.eventBus.emit('ports:all-ports-removed', { element });
		return true;
	}

	/**
	 * Calculates position for a new port on a side
	 */
	calculatePortPosition(side, index) {
		const totalPorts = index + 1;
		const step = 100 / (totalPorts + 1);
		const position = step * (index + 1);

		switch (side) {
			case 'top':
				return { x: `${position}%`, y: '0%' };
			case 'right':
				return { x: '100%', y: `${position}%` };
			case 'bottom':
				return { x: `${position}%`, y: '100%' };
			case 'left':
				return { x: '0%', y: `${position}%` };
			default:
				return { x: '50%', y: '50%' };
		}
	}

	/**
	 * Redistributes ports evenly on a side
	 */
	redistributePortsOnSide(element, side) {
		const elementId = element.id;
		const portStates = this.stateStore.get('ports.states');
		const elementPortState = portStates.get(elementId);

		if (!elementPortState || !elementPortState[side]) return;

		const totalPorts = elementPortState[side].length;
		if (totalPorts === 0) return;

		const step = 100 / (totalPorts + 1);

		elementPortState[side].forEach((port, index) => {
			const position = step * (index + 1);
			let newArgs;

			switch (side) {
				case 'top':
					newArgs = { x: `${position}%`, y: '0%' };
					break;
				case 'right':
					newArgs = { x: '100%', y: `${position}%` };
					break;
				case 'bottom':
					newArgs = { x: `${position}%`, y: '100%' };
					break;
				case 'left':
					newArgs = { x: '0%', y: `${position}%` };
					break;
			}

			element.portProp(port.id, 'args', newArgs);
		});
	}

	/**
	 * Updates visual appearance of ports based on their state
	 */
	updatePortVisuals(element) {
		const elementId = element.id;
		const portStates = this.stateStore.get('ports.states');
		const elementPortState = portStates.get(elementId);

		if (!elementPortState) return;

		const visiblePorts = this.stateStore.get('ports.visible');
		const shouldShowElement = visiblePorts.has(elementId);

		Object.values(elementPortState).flat().forEach(port => {
			if (port.occupied) {
				// Занятый порт - ВСЕГДА скрыт, только цвета для дебага
				element.portProp(port.id, 'attrs/circle/stroke', '#ff4444');
				element.portProp(port.id, 'attrs/circle/strokeWidth', 3);
				element.portProp(port.id, 'attrs/circle/fill', '#ffcccc');
				// ПРИНУДИТЕЛЬНО СКРЫВАЕМ ЗАНЯТЫЕ ПОРТЫ
				element.portProp(port.id, 'attrs/circle/fill-opacity', 0);
				element.portProp(port.id, 'attrs/circle/stroke-opacity', 0);
				element.portProp(port.id, 'attrs/circle/opacity', 0);
				element.portProp(port.id, 'attrs/circle/pointer-events', 'none');
			} else {
				// Свободный порт
				element.portProp(port.id, 'attrs/circle/stroke', '#31d0c6');
				element.portProp(port.id, 'attrs/circle/strokeWidth', 2);
				element.portProp(port.id, 'attrs/circle/fill', '#ffffff');
				
				// Показываем только если элемент должен показывать порты
				if (shouldShowElement) {
					element.portProp(port.id, 'attrs/circle/fill-opacity', 1);
					element.portProp(port.id, 'attrs/circle/stroke-opacity', 1);
					element.portProp(port.id, 'attrs/circle/opacity', 1);
					element.portProp(port.id, 'attrs/circle/pointer-events', 'auto');
				} else {
					element.portProp(port.id, 'attrs/circle/fill-opacity', 0);
					element.portProp(port.id, 'attrs/circle/stroke-opacity', 0);
					element.portProp(port.id, 'attrs/circle/opacity', 0);
					element.portProp(port.id, 'attrs/circle/pointer-events', 'none');
				}
			}
		});
	}

	/**
	 * Shows ports for an element
	 */
	showElementPorts(element) {
		if (!element) return;

		const elementId = element.id;
		const visiblePorts = this.stateStore.get('ports.visible');
		const portStates = this.stateStore.get('ports.states');

		if (!portStates.has(elementId)) {
			this.initializeElementPorts(element);
		}

		const elementPortState = portStates.get(elementId);
		console.log(`🔵 showElementPorts для элемента ${elementId}:`, elementPortState);
		
		Object.values(elementPortState).flat().forEach(port => {
			console.log(`🔍 Порт ${port.id}: occupied=${port.occupied}`);
			
			// ЗАЕБАЛСЯ! ЗАНЯТЫЕ ПОРТЫ НЕ ПОКАЗЫВАЕМ НИКОГДА!
			if (port.occupied) {
				console.log(`🚫 СКРЫВАЕМ занятый порт ${port.id}`);
				element.portProp(port.id, 'attrs/circle/fill-opacity', 0);
				element.portProp(port.id, 'attrs/circle/stroke-opacity', 0);
				element.portProp(port.id, 'attrs/circle/opacity', 0);
				element.portProp(port.id, 'attrs/circle/pointer-events', 'none');
			} else {
				console.log(`👁️ ПОКАЗЫВАЕМ свободный порт ${port.id}`);
				element.portProp(port.id, 'attrs/circle/fill-opacity', 1);
				element.portProp(port.id, 'attrs/circle/stroke-opacity', 1);
				element.portProp(port.id, 'attrs/circle/opacity', 1);
				element.portProp(port.id, 'attrs/circle/pointer-events', 'auto');
			}
		});

		visiblePorts.add(elementId);
		this.stateStore.set('ports.visible', visiblePorts);
		this.updatePortVisuals(element);

		this.eventBus.emit('ports:element-ports-shown', { element });
	}

	/**
	 * Hides ports for an element
	 */
	hideElementPorts(element) {
		if (!element) return;

		const elementId = element.id;
		const visiblePorts = this.stateStore.get('ports.visible');
		const portStates = this.stateStore.get('ports.states');
		const elementPortState = portStates.get(elementId);

		if (!elementPortState) return;

		Object.values(elementPortState).flat().forEach(port => {
			element.portProp(port.id, 'attrs/circle/fill-opacity', 0);
			element.portProp(port.id, 'attrs/circle/stroke-opacity', 0);
			element.portProp(port.id, 'attrs/circle/opacity', 0);
			element.portProp(port.id, 'attrs/circle/pointer-events', 'none');
		});

		visiblePorts.delete(elementId);
		this.stateStore.set('ports.visible', visiblePorts);

		this.eventBus.emit('ports:element-ports-hidden', { element });
	}

	/**
	 * Принудительно скрывает все порты элемента (для борьбы с JointJS глюками)
	 */
	forceHideAllElementPorts(element) {
		if (!element) return;

		const elementId = element.id;
		const portStates = this.stateStore.get('ports.states');
		const elementPortState = portStates.get(elementId);

		if (!elementPortState) return;

		// Проходим по всем портам и принудительно скрываем их
		// Если элемент в списке видимых - показываем только свободные порты
		const visiblePorts = this.stateStore.get('ports.visible');
		const shouldShowElement = visiblePorts.has(elementId);
		
		Object.values(elementPortState).flat().forEach(port => {
			if (shouldShowElement && !port.occupied) {
				// Элемент должен показывать порты и этот порт свободен - показываем
				element.portProp(port.id, 'attrs/circle/fill-opacity', 1);
				element.portProp(port.id, 'attrs/circle/stroke-opacity', 1);
				element.portProp(port.id, 'attrs/circle/opacity', 1);
				element.portProp(port.id, 'attrs/circle/pointer-events', 'auto');
			} else {
				// Во всех остальных случаях - скрываем нахуй
				element.portProp(port.id, 'attrs/circle/fill-opacity', 0);
				element.portProp(port.id, 'attrs/circle/stroke-opacity', 0);
				element.portProp(port.id, 'attrs/circle/opacity', 0);
				element.portProp(port.id, 'attrs/circle/pointer-events', 'none');
			}
		});
	}

	/**
	 * Gets free ports for an element
	 */
	getFreePorts(element) {
		const elementId = element.id;
		const portStates = this.stateStore.get('ports.states');
		const elementPortState = portStates.get(elementId);

		if (!elementPortState) return [];

		return Object.values(elementPortState).flat().filter(port => !port.occupied);
	}

	/**
	 * Gets free ports on a specific side
	 */
	getFreePortsOnSide(element, side) {
		const elementId = element.id;
		const portStates = this.stateStore.get('ports.states');
		const elementPortState = portStates.get(elementId);

		if (!elementPortState || !elementPortState[side]) return [];

		return elementPortState[side].filter(port => !port.occupied);
	}

	/**
	 * Checks if a port is available for connection
	 */
	isPortAvailable(element, portId) {
		const elementId = element.id;
		const portStates = this.stateStore.get('ports.states');
		const elementPortState = portStates.get(elementId);

		if (!elementPortState) return false;

		const port = Object.values(elementPortState).flat().find(p => p.id === portId);
		return port && !port.occupied && port.magnet;
	}

	/**
	 * Occupies a port with a link
	 */
	occupyPort(element, portId, linkId) {
		const elementId = element.id;
		const portStates = this.stateStore.get('ports.states');
		const elementPortState = portStates.get(elementId);

		if (!elementPortState) return false;

		const port = Object.values(elementPortState).flat().find(p => p.id === portId);
		if (!port) return false;

		port.occupied = true;
		port.linkId = linkId;

		portStates.set(elementId, elementPortState);
		this.stateStore.set('ports.states', portStates);
		this.updatePortVisuals(element);

		this.eventBus.emit('ports:port-occupied', { element, portId, linkId });
		return true;
	}

	/**
	 * Frees a port from a link
	 */
	freePort(element, portId) {
		const elementId = element.id;
		const portStates = this.stateStore.get('ports.states');
		const elementPortState = portStates.get(elementId);

		if (!elementPortState) return false;

		const port = Object.values(elementPortState).flat().find(p => p.id === portId);
		if (!port) return false;

		port.occupied = false;
		port.linkId = null;

		portStates.set(elementId, elementPortState);
		this.stateStore.set('ports.states', portStates);
		this.updatePortVisuals(element);

		this.eventBus.emit('ports:port-freed', { element, portId });
		return true;
	}

	/**
	 * Handles link connection event
	 */
	handleLinkConnect(link) {
		const sourceElement = link.getSourceElement();
		const targetElement = link.getTargetElement();
		const sourcePort = link.get('source').port;
		const targetPort = link.get('target').port;

		if (sourceElement && sourcePort) {
			this.occupyPort(sourceElement, sourcePort, link.id);
		}

		if (targetElement && targetPort) {
			this.occupyPort(targetElement, targetPort, link.id);
		}

		this.eventBus.emit('ports:link-connected', { link, sourceElement, targetElement });
	}

	/**
	 * Handles link disconnection event
	 */
	handleLinkDisconnect(link) {
		const sourceElement = link.getSourceElement();
		const targetElement = link.getTargetElement();
		const sourcePort = link.get('source').port;
		const targetPort = link.get('target').port;

		if (sourceElement && sourcePort) {
			this.freePort(sourceElement, sourcePort);
		}

		if (targetElement && targetPort) {
			this.freePort(targetElement, targetPort);
		}

		this.eventBus.emit('ports:link-disconnected', { link, sourceElement, targetElement });
	}

	/**
	 * Validates a connection between two ports
	 */
	validateConnection(sourceElement, sourcePortId, targetElement, targetPortId) {
		if (sourceElement === targetElement) {
			return { valid: false, reason: 'Cannot connect element to itself' };
		}

		if (!this.isPortAvailable(sourceElement, sourcePortId)) {
			return { valid: false, reason: 'Source port is not available' };
		}

		if (!this.isPortAvailable(targetElement, targetPortId)) {
			return { valid: false, reason: 'Target port is not available' };
		}

		return { valid: true };
	}

	/**
	 * Creates a connection between two elements using their free ports
	 */
	createConnection(sourceElement, targetElement) {
		const sourceFreePorts = this.getFreePorts(sourceElement);
		const targetFreePorts = this.getFreePorts(targetElement);

		if (sourceFreePorts.length === 0 || targetFreePorts.length === 0) {
			return null;
		}

		const sourcePort = sourceFreePorts[0];
		const targetPort = targetFreePorts[0];

		const link = this.graphService.createLink({
			source: { id: sourceElement.id, port: sourcePort.id },
			target: { id: targetElement.id, port: targetPort.id }
		});

		return link;
	}

	/**
	 * Cleans up port state when element is removed
	 */
	cleanupElementPorts(element) {
		const elementId = element.id;
		const portStates = this.stateStore.get('ports.states');
		const visiblePorts = this.stateStore.get('ports.visible');

		portStates.delete(elementId);
		visiblePorts.delete(elementId);

		this.stateStore.setBatch({
			'ports.states': portStates,
			'ports.visible': visiblePorts
		});

		this.eventBus.emit('ports:element-cleaned', { element });
	}

	/**
	 * Synchronizes all port states with current graph
	 */
	syncAllPortStates() {
		const portStates = this.stateStore.get('ports.states');
		portStates.clear();

		this.graphService.graph.getElements().forEach(element => {
			this.initializeElementPorts(element);
		});

		this.graphService.graph.getLinks().forEach(link => {
			this.handleLinkConnect(link);
		});

		this.stateStore.set('ports.states', portStates);
		this.eventBus.emit('ports:all-states-synced');
	}

	/**
	 * Gets port statistics for an element
	 */
	getElementPortStats(element) {
		const elementId = element.id;
		const portStates = this.stateStore.get('ports.states');
		const elementPortState = portStates.get(elementId);

		if (!elementPortState) return null;

		const stats = {
			total: 0,
			occupied: 0,
			free: 0,
			bySide: {}
		};

		Object.entries(elementPortState).forEach(([side, ports]) => {
			const sideStats = {
				total: ports.length,
				occupied: ports.filter(p => p.occupied).length,
				free: ports.filter(p => !p.occupied).length
			};

			stats.total += sideStats.total;
			stats.occupied += sideStats.occupied;
			stats.free += sideStats.free;
			stats.bySide[side] = sideStats;
		});

		return stats;
	}

	/**
	 * Gets global port service statistics
	 */
	getStats() {
		const portStates = this.stateStore.get('ports.states');
		const visiblePorts = this.stateStore.get('ports.visible');

		let totalPorts = 0;
		let occupiedPorts = 0;

		for (const elementPortState of portStates.values()) {
			Object.values(elementPortState).flat().forEach(port => {
				totalPorts++;
				if (port.occupied) occupiedPorts++;
			});
		}

		return {
			totalElements: portStates.size,
			totalPorts,
			occupiedPorts,
			freePorts: totalPorts - occupiedPorts,
			visibleElements: visiblePorts.size,
			portUtilization: totalPorts > 0 ? (occupiedPorts / totalPorts) * 100 : 0
		};
	}

	/**
	 * Enables debug mode
	 */
	setDebugMode(enabled) {
		this.debugMode = enabled;
	}

	/**
	 * Destroys the service
	 */
	destroy() {
		const portStates = this.stateStore.get('ports.states');
		const visiblePorts = this.stateStore.get('ports.visible');
		
		portStates.clear();
		visiblePorts.clear();
		
		this.stateStore.setBatch({
			'ports.states': portStates,
			'ports.visible': visiblePorts
		});

		this.initialized = false;
		this.eventBus.emit('ports:service-destroyed');
	}
}