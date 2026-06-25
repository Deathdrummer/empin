 
/**
 * ValidationService - Service for validating connections, elements, and graph operations
 */
export class ValidationService {
	constructor(eventBus, stateStore, graphService, portService) {
		this.eventBus = eventBus;
		this.stateStore = stateStore;
		this.graphService = graphService;
		this.portService = portService;
		this.initialized = false;
		this.debugMode = false;
		this.validationRules = new Map();
		this.customValidators = new Map();
		
		this.setupDefaultValidationRules();
		this.bindEventHandlers();
	}

	/**
	 * Initializes the validation service
	 */
	init() {
		if (this.initialized) {
			console.warn('ValidationService: Already initialized');
			return;
		}

		this.initialized = true;
		this.eventBus.emit('validation:service-initialized');
	}

	/**
	 * Sets up default validation rules
	 */
	setupDefaultValidationRules() {
		this.addValidationRule('connection', 'no-self-connection', (context) => {
			const { sourceElement, targetElement } = context;
			return {
				valid: sourceElement !== targetElement,
				reason: sourceElement === targetElement ? 'Cannot connect element to itself' : null
			};
		});

		this.addValidationRule('connection', 'ports-available', (context) => {
			const { sourceElement, sourcePortId, targetElement, targetPortId } = context;
			
			if (!sourcePortId || !targetPortId) {
				return { valid: false, reason: 'Connection must use ports' };
			}

			const sourceAvailable = this.portService.isPortAvailable(sourceElement, sourcePortId);
			const targetAvailable = this.portService.isPortAvailable(targetElement, targetPortId);

			if (!sourceAvailable) {
				return { valid: false, reason: 'Source port is not available' };
			}

			if (!targetAvailable) {
				return { valid: false, reason: 'Target port is not available' };
			}

			return { valid: true };
		});

		this.addValidationRule('connection', 'no-duplicate-connections', (context) => {
			const { sourceElement, sourcePortId, targetElement, targetPortId } = context;
			
			const existingLinks = this.graphService.graph.getLinks();
			const duplicateExists = existingLinks.some(link => {
				const linkSource = link.get('source');
				const linkTarget = link.get('target');
				
				return (linkSource.id === sourceElement.id && 
						linkSource.port === sourcePortId &&
						linkTarget.id === targetElement.id && 
						linkTarget.port === targetPortId) ||
					   (linkSource.id === targetElement.id && 
						linkSource.port === targetPortId &&
						linkTarget.id === sourceElement.id && 
						linkTarget.port === sourcePortId);
			});

			return {
				valid: !duplicateExists,
				reason: duplicateExists ? 'Connection already exists between these ports' : null
			};
		});

		this.addValidationRule('element', 'valid-position', (context) => {
			const { element, position } = context;
			const canvasWidth = this.stateStore.get('canvas.width');
			const canvasHeight = this.stateStore.get('canvas.height');
			const size = element.size();

			const isValid = position.x >= 0 && 
							position.y >= 0 && 
							position.x + size.width <= canvasWidth && 
							position.y + size.height <= canvasHeight;

			return {
				valid: isValid,
				reason: isValid ? null : 'Element position is outside canvas bounds'
			};
		});

		this.addValidationRule('element', 'valid-size', (context) => {
			const { element, size } = context;
			const minSize = 10;
			const maxSize = 500;

			const isValid = size.width >= minSize && 
							size.height >= minSize && 
							size.width <= maxSize && 
							size.height <= maxSize;

			return {
				valid: isValid,
				reason: isValid ? null : `Element size must be between ${minSize}x${minSize} and ${maxSize}x${maxSize}`
			};
		});

		this.addValidationRule('element', 'no-overlap', (context) => {
			const { element, position, size } = context;
			const elementBounds = joint.g.rect(position.x, position.y, size.width, size.height);
			
			const overlapping = this.graphService.graph.getElements()
				.filter(el => el !== element)
				.some(el => {
					const elBounds = el.getBBox();
					return elementBounds.intersect(elBounds);
				});

			return {
				valid: !overlapping,
				reason: overlapping ? 'Element overlaps with existing element' : null
			};
		});

		this.addValidationRule('graph', 'max-elements', (context) => {
			const maxElements = context.maxElements || 1000;
			const currentCount = this.graphService.graph.getElements().length;

			return {
				valid: currentCount < maxElements,
				reason: currentCount >= maxElements ? `Maximum ${maxElements} elements allowed` : null
			};
		});

		this.addValidationRule('graph', 'max-links', (context) => {
			const maxLinks = context.maxLinks || 2000;
			const currentCount = this.graphService.graph.getLinks().length;

			return {
				valid: currentCount < maxLinks,
				reason: currentCount >= maxLinks ? `Maximum ${maxLinks} links allowed` : null
			};
		});
	}

	/**
	 * Binds service to external events
	 */
	bindEventHandlers() {
		this.eventBus.on('connection:validate', (event) => {
			this.validateConnection(event.source, event.target);
		});

		this.eventBus.on('element:validate-move', (event) => {
			this.validateElementMove(event.element, event.newPosition);
		});

		this.eventBus.on('element:validate-resize', (event) => {
			this.validateElementResize(event.element, event.newSize);
		});

		this.eventBus.on('graph:validate', () => {
			this.validateGraph();
		});
	}

	/**
	 * Adds a validation rule
	 */
	addValidationRule(category, name, validator) {
		if (!this.validationRules.has(category)) {
			this.validationRules.set(category, new Map());
		}

		this.validationRules.get(category).set(name, validator);

		this.eventBus.emit('validation:rule-added', {
			category,
			name,
			validator
		});
	}

	/**
	 * Removes a validation rule
	 */
	removeValidationRule(category, name) {
		if (this.validationRules.has(category)) {
			const categoryRules = this.validationRules.get(category);
			const removed = categoryRules.delete(name);

			if (categoryRules.size === 0) {
				this.validationRules.delete(category);
			}

			if (removed) {
				this.eventBus.emit('validation:rule-removed', {
					category,
					name
				});
			}

			return removed;
		}
		return false;
	}

	/**
	 * Validates a connection attempt
	 */
	validateConnection(sourceInfo, targetInfo) {
		const context = {
			sourceElement: sourceInfo.cellView.model,
			sourcePortId: sourceInfo.magnet ? sourceInfo.magnet.getAttribute('port') : null,
			targetElement: targetInfo.cellView.model,
			targetPortId: targetInfo.magnet ? targetInfo.magnet.getAttribute('port') : null,
			sourceMagnet: sourceInfo.magnet,
			targetMagnet: targetInfo.magnet
		};

		const result = this.runValidationRules('connection', context);

		this.eventBus.emit('validation:connection-validated', {
			context,
			result
		});

		return result;
	}

	/**
	 * Validates element movement
	 */
	validateElementMove(element, newPosition) {
		const context = {
			element,
			position: newPosition,
			size: element.size(),
			originalPosition: element.position()
		};

		const result = this.runValidationRules('element', context);

		this.eventBus.emit('validation:element-move-validated', {
			element,
			newPosition,
			result
		});

		return result;
	}

	/**
	 * Validates element resizing
	 */
	validateElementResize(element, newSize) {
		const context = {
			element,
			size: newSize,
			position: element.position(),
			originalSize: element.size()
		};

		const result = this.runValidationRules('element', context);

		this.eventBus.emit('validation:element-resize-validated', {
			element,
			newSize,
			result
		});

		return result;
	}

	/**
	 * Validates element creation
	 */
	validateElementCreation(elementType, position, size) {
		const context = {
			elementType,
			position,
			size,
			element: null
		};

		const result = this.runValidationRules('element', context);

		this.eventBus.emit('validation:element-creation-validated', {
			elementType,
			position,
			size,
			result
		});

		return result;
	}

	/**
	 * Validates the entire graph
	 */
	validateGraph() {
		const context = {
			elementCount: this.graphService.graph.getElements().length,
			linkCount: this.graphService.graph.getLinks().length,
			graph: this.graphService.graph
		};

		const result = this.runValidationRules('graph', context);

		this.eventBus.emit('validation:graph-validated', {
			context,
			result
		});

		return result;
	}

	/**
	 * Validates JSON data before loading
	 */
	validateGraphData(jsonData) {
		const errors = [];
		const warnings = [];

		try {
			if (!jsonData.cells || !Array.isArray(jsonData.cells)) {
				errors.push('Invalid graph data: missing or invalid cells array');
				return { valid: false, errors, warnings };
			}

			jsonData.cells.forEach((cell, index) => {
				if (!cell.id) {
					errors.push(`Cell at index ${index} missing required id field`);
				}

				if (!cell.type) {
					errors.push(`Cell at index ${index} missing required type field`);
				}

				if (cell.type && cell.type.includes('Link')) {
					if (!cell.source || !cell.target) {
						errors.push(`Link at index ${index} missing source or target`);
					}
				}

				if (cell.type && cell.type.includes('Element')) {
					if (!cell.position) {
						warnings.push(`Element at index ${index} missing position, will use default`);
					}

					if (!cell.size) {
						warnings.push(`Element at index ${index} missing size, will use default`);
					}
				}
			});

			const duplicateIds = this.findDuplicateIds(jsonData.cells);
			if (duplicateIds.length > 0) {
				errors.push(`Duplicate cell IDs found: ${duplicateIds.join(', ')}`);
			}

			const orphanedLinks = this.findOrphanedLinks(jsonData.cells);
			if (orphanedLinks.length > 0) {
				warnings.push(`Found ${orphanedLinks.length} links with missing source or target elements`);
			}

		} catch (error) {
			errors.push(`Invalid JSON data: ${error.message}`);
		}

		const result = {
			valid: errors.length === 0,
			errors,
			warnings
		};

		this.eventBus.emit('validation:graph-data-validated', {
			jsonData,
			result
		});

		return result;
	}

	/**
	 * Finds duplicate IDs in cell data
	 */
	findDuplicateIds(cells) {
		const ids = cells.map(cell => cell.id);
		const duplicates = ids.filter((id, index) => ids.indexOf(id) !== index);
		return [...new Set(duplicates)];
	}

	/**
	 * Finds links that reference non-existent elements
	 */
	findOrphanedLinks(cells) {
		const elementIds = new Set(cells
			.filter(cell => !cell.type.includes('Link'))
			.map(cell => cell.id)
		);

		return cells
			.filter(cell => cell.type.includes('Link'))
			.filter(link => {
				const sourceId = typeof link.source === 'object' ? link.source.id : link.source;
				const targetId = typeof link.target === 'object' ? link.target.id : link.target;
				return !elementIds.has(sourceId) || !elementIds.has(targetId);
			});
	}

	/**
	 * Runs validation rules for a specific category
	 */
	runValidationRules(category, context) {
		const categoryRules = this.validationRules.get(category);
		
		if (!categoryRules) {
			return { valid: true, errors: [], warnings: [] };
		}

		const errors = [];
		const warnings = [];
		let valid = true;

		for (const [ruleName, validator] of categoryRules) {
			try {
				const result = validator(context);
				
				if (!result.valid) {
					valid = false;
					if (result.reason) {
						errors.push({
							rule: ruleName,
							reason: result.reason
						});
					}
				}

				if (result.warning) {
					warnings.push({
						rule: ruleName,
						warning: result.warning
					});
				}

			} catch (error) {
				console.error(`ValidationService: Error in rule '${ruleName}':`, error);
				valid = false;
				errors.push({
					rule: ruleName,
					reason: `Validation rule error: ${error.message}`
				});
			}
		}

		return { valid, errors, warnings };
	}

	/**
	 * Adds a custom validator function
	 */
	addCustomValidator(name, validator) {
		this.customValidators.set(name, validator);

		this.eventBus.emit('validation:custom-validator-added', {
			name,
			validator
		});
	}

	/**
	 * Removes a custom validator
	 */
	removeCustomValidator(name) {
		const removed = this.customValidators.delete(name);

		if (removed) {
			this.eventBus.emit('validation:custom-validator-removed', {
				name
			});
		}

		return removed;
	}

	/**
	 * Runs a custom validator
	 */
	runCustomValidator(name, context) {
		const validator = this.customValidators.get(name);
		
		if (!validator) {
			return { valid: false, reason: `Custom validator '${name}' not found` };
		}

		try {
			return validator(context);
		} catch (error) {
			console.error(`ValidationService: Error in custom validator '${name}':`, error);
			return { valid: false, reason: `Custom validator error: ${error.message}` };
		}
	}

	/**
	 * Validates element constraints
	 */
	validateElementConstraints(element) {
		const constraints = element.get('constraints') || {};
		const errors = [];

		if (constraints.minConnections !== undefined) {
			const connectedLinks = this.graphService.getConnectedLinks(element);
			if (connectedLinks.length < constraints.minConnections) {
				errors.push(`Element requires at least ${constraints.minConnections} connections`);
			}
		}

		if (constraints.maxConnections !== undefined) {
			const connectedLinks = this.graphService.getConnectedLinks(element);
			if (connectedLinks.length > constraints.maxConnections) {
				errors.push(`Element cannot have more than ${constraints.maxConnections} connections`);
			}
		}

		if (constraints.allowedTargets !== undefined) {
			const connectedLinks = this.graphService.getConnectedLinks(element);
			const invalidConnections = connectedLinks.filter(link => {
				const targetElement = link.getTargetElement();
				const sourceElement = link.getSourceElement();
				const otherElement = targetElement === element ? sourceElement : targetElement;
				
				if (!otherElement) return false;
				
				const otherType = otherElement.get('type');
				return !constraints.allowedTargets.includes(otherType);
			});

			if (invalidConnections.length > 0) {
				errors.push(`Element has invalid connections to disallowed element types`);
			}
		}

		return {
			valid: errors.length === 0,
			errors: errors.map(error => ({ rule: 'constraints', reason: error }))
		};
	}

	/**
	 * Gets validation statistics
	 */
	getValidationStats() {
		const categoryCount = this.validationRules.size;
		let totalRules = 0;

		for (const categoryRules of this.validationRules.values()) {
			totalRules += categoryRules.size;
		}

		return {
			categories: categoryCount,
			totalRules,
			customValidators: this.customValidators.size,
			rulesPerCategory: Object.fromEntries(
				Array.from(this.validationRules.entries()).map(([category, rules]) => [
					category,
					rules.size
				])
			)
		};
	}

	/**
	 * Gets all validation rules for a category
	 */
	getValidationRules(category) {
		const categoryRules = this.validationRules.get(category);
		return categoryRules ? Array.from(categoryRules.keys()) : [];
	}

	/**
	 * Enables or disables a validation rule
	 */
	setRuleEnabled(category, ruleName, enabled) {
		const categoryRules = this.validationRules.get(category);
		
		if (!categoryRules || !categoryRules.has(ruleName)) {
			return false;
		}

		const rule = categoryRules.get(ruleName);
		rule.enabled = enabled;

		this.eventBus.emit('validation:rule-toggled', {
			category,
			ruleName,
			enabled
		});

		return true;
	}

	/**
	 * Checks if a validation rule is enabled
	 */
	isRuleEnabled(category, ruleName) {
		const categoryRules = this.validationRules.get(category);
		
		if (!categoryRules || !categoryRules.has(ruleName)) {
			return false;
		}

		const rule = categoryRules.get(ruleName);
		return rule.enabled !== false;
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
		this.validationRules.clear();
		this.customValidators.clear();
		this.initialized = false;

		this.eventBus.emit('validation:service-destroyed');
	}
}