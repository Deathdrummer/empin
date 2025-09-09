 
import { BaseCommand } from './BaseCommand.js';

/**
 * AddElementCommand - Command for adding new elements to the graph
 */
export class AddElementCommand extends BaseCommand {
	constructor(graphService, validationService, options = {}) {
		const {
			elementType = 'rectangle',
			position = { x: 100, y: 100 },
			size = { width: 60, height: 40 },
			attrs = {},
			ports = null
		} = options;

		super(`Add ${elementType} element`);
		
		this.graphService = graphService;
		this.validationService = validationService;
		this.elementType = elementType;
		this.position = position;
		this.size = size;
		this.attrs = attrs;
		this.ports = ports;
		this.createdElement = null;
		this.snapToGrid = options.snapToGrid !== false;
		this.gridSize = options.gridSize || 10;
	}

	/**
	 * Validates command parameters before execution
	 */
	validateParameters() {
		const errors = [];

		if (!this.elementType || typeof this.elementType !== 'string') {
			errors.push('Element type must be a non-empty string');
		}

		if (!this.position || typeof this.position.x !== 'number' || typeof this.position.y !== 'number') {
			errors.push('Position must contain valid x and y coordinates');
		}

		if (!this.size || typeof this.size.width !== 'number' || typeof this.size.height !== 'number') {
			errors.push('Size must contain valid width and height values');
		}

		if (this.size.width <= 0 || this.size.height <= 0) {
			errors.push('Size dimensions must be positive numbers');
		}

		return {
			valid: errors.length === 0,
			errors
		};
	}

	/**
	 * Validates element creation with business rules
	 */
	isValid() {
		const paramValidation = this.validateParameters();
		if (!paramValidation.valid) {
			return false;
		}

		const position = this.snapToGrid ? this.getSnappedPosition() : this.position;
		
		const validation = this.validationService.validateElementCreation(
			this.elementType,
			position,
			this.size
		);

		return validation.valid;
	}

	/**
	 * Calculates grid-snapped position
	 */
	getSnappedPosition() {
		if (!this.snapToGrid) {
			return this.position;
		}

		return {
			x: Math.round(this.position.x / this.gridSize) * this.gridSize,
			y: Math.round(this.position.y / this.gridSize) * this.gridSize
		};
	}

	/**
	 * Executes the add element command
	 */
	execute() {
		try {
			if (!this.isValid()) {
				return false;
			}

			const finalPosition = this.getSnappedPosition();
			
			this.createdElement = this.graphService.createElement({
				type: this.elementType,
				position: finalPosition,
				size: this.size,
				attrs: this.attrs,
				ports: this.ports
			});

			if (!this.createdElement) {
				return false;
			}

			this.setMetadata('elementId', this.createdElement.id);
			this.setMetadata('actualPosition', finalPosition);
			this.markAsExecuted();

			return this.createdElement;

		} catch (error) {
			console.error('AddElementCommand: Execution failed:', error);
			return false;
		}
	}

	/**
	 * Undoes the add element command
	 */
	undo() {
		try {
			if (!this.executed || !this.createdElement) {
				return false;
			}

			const elementId = this.createdElement.id;
			this.createdElement.remove();
			this.createdElement = null;
			
			this.markAsNotExecuted();
			this.setMetadata('removedElementId', elementId);

			return true;

		} catch (error) {
			console.error('AddElementCommand: Undo failed:', error);
			return false;
		}
	}

	/**
	 * Redoes the add element command
	 */
	redo() {
		if (this.executed) {
			return true;
		}

		return this.execute();
	}

	/**
	 * Gets entities affected by this command
	 */
	getAffectedEntities() {
		if (this.createdElement) {
			return [this.createdElement.id];
		}
		
		const elementId = this.getMetadata('elementId') || this.getMetadata('removedElementId');
		return elementId ? [elementId] : [];
	}

	/**
	 * Checks if command affects specific entities
	 */
	affectsEntities(entityIds) {
		const affectedIds = this.getAffectedEntities();
		return affectedIds.some(id => entityIds.includes(id));
	}

	/**
	 * Gets the created element instance
	 */
	getCreatedElement() {
		return this.createdElement;
	}

	/**
	 * Gets the element ID (whether created or removed)
	 */
	getElementId() {
		return this.getMetadata('elementId') || this.getMetadata('removedElementId');
	}

	/**
	 * Checks if this command can be merged with another add command
	 */
	canMergeWith(otherCommand) {
		if (!(otherCommand instanceof AddElementCommand)) {
			return false;
		}

		const timeDiff = Math.abs(this.timestamp - otherCommand.timestamp);
		const maxMergeTime = 1000; // 1 second

		if (timeDiff > maxMergeTime) {
			return false;
		}

		const distance = Math.sqrt(
			Math.pow(this.position.x - otherCommand.position.x, 2) +
			Math.pow(this.position.y - otherCommand.position.y, 2)
		);

		return distance < 100; // Elements are close to each other
	}

	/**
	 * Merges this command with another compatible add command
	 */
	mergeWith(otherCommand) {
		if (!this.canMergeWith(otherCommand)) {
			return null;
		}

		const mergedCommand = new AddMultipleElementsCommand([this, otherCommand]);
		mergedCommand.setDescription('Add multiple elements');
		mergedCommand.setGroupId(this.groupId || this.id);

		return mergedCommand;
	}

	/**
	 * Creates a clone with different position
	 */
	cloneAt(newPosition) {
		const cloned = new AddElementCommand(this.graphService, this.validationService, {
			elementType: this.elementType,
			position: newPosition,
			size: this.size,
			attrs: { ...this.attrs },
			ports: this.ports ? { ...this.ports } : null,
			snapToGrid: this.snapToGrid,
			gridSize: this.gridSize
		});

		cloned.setDescription(`Clone ${this.elementType} element`);
		return cloned;
	}

	/**
	 * Gets estimated execution time
	 */
	getEstimatedExecutionTime() {
		return 50; // 50ms estimated for element creation
	}

	/**
	 * Serializes command-specific data
	 */
	toJSON() {
		const baseData = super.toJSON();
		
		return {
			...baseData,
			elementType: this.elementType,
			position: this.position,
			size: this.size,
			attrs: this.attrs,
			ports: this.ports,
			snapToGrid: this.snapToGrid,
			gridSize: this.gridSize,
			createdElementId: this.createdElement ? this.createdElement.id : null
		};
	}

	/**
	 * Restores command from JSON data
	 */
	static fromJSON(data, graphService, validationService) {
		const command = new AddElementCommand(graphService, validationService, {
			elementType: data.elementType,
			position: data.position,
			size: data.size,
			attrs: data.attrs,
			ports: data.ports,
			snapToGrid: data.snapToGrid,
			gridSize: data.gridSize
		});

		command.id = data.id;
		command.description = data.description;
		command.executed = data.executed;
		command.timestamp = data.timestamp;
		command.groupId = data.groupId;
		command.metadata = data.metadata || {};

		if (data.createdElementId && data.executed) {
			command.createdElement = graphService.getElementById(data.createdElementId);
		}

		return command;
	}
}

/**
 * AddMultipleElementsCommand - Command for adding multiple elements as a batch
 */
export class AddMultipleElementsCommand extends BaseCommand {
	constructor(addCommands = []) {
		super('Add multiple elements');
		this.addCommands = addCommands;
		this.createdElements = [];
	}

	/**
	 * Validates all sub-commands
	 */
	isValid() {
		return this.addCommands.every(command => command.isValid());
	}

	/**
	 * Executes all add commands
	 */
	execute() {
		try {
			this.createdElements = [];

			for (const command of this.addCommands) {
				const result = command.execute();
				if (result === false) {
					this.undoCreatedElements();
					return false;
				}
				this.createdElements.push(command.getCreatedElement());
			}

			this.markAsExecuted();
			return this.createdElements;

		} catch (error) {
			this.undoCreatedElements();
			console.error('AddMultipleElementsCommand: Execution failed:', error);
			return false;
		}
	}

	/**
	 * Undoes all created elements
	 */
	undo() {
		try {
			for (let i = this.addCommands.length - 1; i >= 0; i--) {
				const command = this.addCommands[i];
				if (command.executed) {
					command.undo();
				}
			}

			this.createdElements = [];
			this.markAsNotExecuted();
			return true;

		} catch (error) {
			console.error('AddMultipleElementsCommand: Undo failed:', error);
			return false;
		}
	}

	/**
	 * Undoes only the successfully created elements
	 */
	undoCreatedElements() {
		for (let i = this.addCommands.length - 1; i >= 0; i--) {
			const command = this.addCommands[i];
			if (command.executed) {
				try {
					command.undo();
				} catch (error) {
					console.error('AddMultipleElementsCommand: Error undoing element:', error);
				}
			}
		}
		this.createdElements = [];
	}

	/**
	 * Gets all created elements
	 */
	getCreatedElements() {
		return this.createdElements;
	}

	/**
	 * Gets entities affected by this command
	 */
	getAffectedEntities() {
		const entities = new Set();
		this.addCommands.forEach(command => {
			command.getAffectedEntities().forEach(id => entities.add(id));
		});
		return Array.from(entities);
	}

	/**
	 * Adds another add command to the batch
	 */
	addCommand(addCommand) {
		if (addCommand instanceof AddElementCommand) {
			this.addCommands.push(addCommand);
			this.setDescription(`Add ${this.addCommands.length} elements`);
		}
	}

	/**
	 * Gets estimated execution time for all commands
	 */
	getEstimatedExecutionTime() {
		return this.addCommands.reduce((total, cmd) => total + cmd.getEstimatedExecutionTime(), 0);
	}
}