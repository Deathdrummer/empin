import { BaseCommand } from './BaseCommand.js';

/**
 * DeleteElementCommand - Command for removing elements and links from the graph
 */
export class DeleteElementCommand extends BaseCommand {
	constructor(graphService, portService, elements = []) {
		const elementCount = elements.length;
		const description = elementCount === 1 
			? `Delete ${elements[0].isElement() ? 'element' : 'link'}` 
			: `Delete ${elementCount} items`;
		
		super(description);
		
		this.graphService = graphService;
		this.portService = portService;
		this.elementsToDelete = Array.isArray(elements) ? elements : [elements];
		this.deletedData = [];
		this.connectedLinks = [];
		this.restoredElements = [];
	}

	/**
	 * Validates command parameters before execution
	 */
	validateParameters() {
		const errors = [];

		if (!Array.isArray(this.elementsToDelete) || this.elementsToDelete.length === 0) {
			errors.push('No elements specified for deletion');
		}

		this.elementsToDelete.forEach((element, index) => {
			if (!element || typeof element.remove !== 'function') {
				errors.push(`Item at index ${index} is not a valid graph element`);
			}
		});

		return {
			valid: errors.length === 0,
			errors
		};
	}

	/**
	 * Validates element deletion with business rules
	 */
	isValid() {
		const paramValidation = this.validateParameters();
		if (!paramValidation.valid) {
			return false;
		}

		return this.elementsToDelete.every(element => {
			return this.graphService.graph.getCells().includes(element);
		});
	}

	/**
	 * Collects connected links for elements being deleted
	 */
	collectConnectedLinks() {
		const connectedLinks = new Set();
		
		this.elementsToDelete.forEach(element => {
			if (element.isElement()) {
				const links = this.graphService.getConnectedLinks(element);
				links.forEach(link => {
					if (!this.elementsToDelete.includes(link)) {
						connectedLinks.add(link);
					}
				});
			}
		});

		return Array.from(connectedLinks);
	}

	/**
	 * Stores element data for restoration
	 */
	storeElementData(element) {
		const data = {
			id: element.id,
			type: element.get('type'),
			isElement: element.isElement(),
			isLink: element.isLink(),
			serializedData: element.toJSON()
		};

		if (element.isElement()) {
			data.position = element.position();
			data.size = element.size();
			data.attrs = element.get('attrs');
			data.ports = element.getPorts();
			
			if (this.portService) {
				data.portState = this.portService.getElementPortStats(element);
			}
		} else if (element.isLink()) {
			data.source = element.get('source');
			data.target = element.get('target');
			data.attrs = element.get('attrs');
			data.router = element.get('router');
			data.connector = element.get('connector');
			data.labels = element.get('labels');
		}

		return data;
	}

	/**
	 * Executes the delete element command
	 */
	execute() {
		try {
			if (!this.isValid()) {
				return false;
			}

			this.deletedData = [];
			this.connectedLinks = this.collectConnectedLinks();

			this.elementsToDelete.forEach(element => {
				const elementData = this.storeElementData(element);
				this.deletedData.push(elementData);

				if (element.isElement() && this.portService) {
					this.portService.cleanupElementPorts(element);
				}

				element.remove();
			});

			this.connectedLinks.forEach(link => {
				const linkData = this.storeElementData(link);
				this.deletedData.push(linkData);
				link.remove();
			});

			this.setMetadata('deletedCount', this.deletedData.length);
			this.setMetadata('elementIds', this.deletedData.map(d => d.id));
			this.markAsExecuted();

			return true;

		} catch (error) {
			console.error('DeleteElementCommand: Execution failed:', error);
			return false;
		}
	}

	/**
	 * Restores a single element from stored data
	 */
	restoreElementFromData(elementData) {
		try {
			const cellNamespace = this.graphService.cellNamespace;
			const restoredElement = cellNamespace[elementData.type] 
				? new cellNamespace[elementData.type]()
				: joint.util.setByPath({}, elementData.type, joint.shapes, '/').call(joint.shapes);

			if (!restoredElement) {
				console.warn(`DeleteElementCommand: Unknown element type: ${elementData.type}`);
				return null;
			}

			restoredElement.set(elementData.serializedData);
			restoredElement.addTo(this.graphService.graph);

			if (elementData.isElement && this.portService) {
				this.portService.initializeElementPorts(restoredElement);
			}

			return restoredElement;

		} catch (error) {
			console.error('DeleteElementCommand: Failed to restore element:', error);
			return null;
		}
	}

	/**
	 * Undoes the delete element command
	 */
	undo() {
		try {
			if (!this.executed || this.deletedData.length === 0) {
				return false;
			}

			this.restoredElements = [];
			const restoredById = new Map();

			this.deletedData.forEach(elementData => {
				const restored = this.restoreElementFromData(elementData);
				if (restored) {
					this.restoredElements.push(restored);
					restoredById.set(elementData.id, restored);
				}
			});

			this.restoredElements.forEach(element => {
				if (element.isLink()) {
					const source = element.get('source');
					const target = element.get('target');

					if (source.id && restoredById.has(source.id)) {
						source.id = restoredById.get(source.id).id;
					}
					if (target.id && restoredById.has(target.id)) {
						target.id = restoredById.get(target.id).id;
					}

					element.set('source', source);
					element.set('target', target);
				}
			});

			this.markAsNotExecuted();
			return true;

		} catch (error) {
			console.error('DeleteElementCommand: Undo failed:', error);
			this.cleanupPartialRestore();
			return false;
		}
	}

	/**
	 * Cleans up partially restored elements in case of undo failure
	 */
	cleanupPartialRestore() {
		this.restoredElements.forEach(element => {
			try {
				if (element && element.remove) {
					element.remove();
				}
			} catch (error) {
				console.error('DeleteElementCommand: Error cleaning up partial restore:', error);
			}
		});
		this.restoredElements = [];
	}

	/**
	 * Redoes the delete element command
	 */
	redo() {
		if (this.executed) {
			return true;
		}

		this.restoredElements.forEach(element => {
			try {
				if (element.isElement() && this.portService) {
					this.portService.cleanupElementPorts(element);
				}
				element.remove();
			} catch (error) {
				console.error('DeleteElementCommand: Error during redo:', error);
			}
		});

		this.restoredElements = [];
		this.markAsExecuted();
		return true;
	}

	/**
	 * Gets entities affected by this command
	 */
	getAffectedEntities() {
		if (this.deletedData.length > 0) {
			return this.deletedData.map(data => data.id);
		}
		
		return this.elementsToDelete.map(element => element.id);
	}

	/**
	 * Checks if command affects specific entities
	 */
	affectsEntities(entityIds) {
		const affectedIds = this.getAffectedEntities();
		return affectedIds.some(id => entityIds.includes(id));
	}

	/**
	 * Gets deleted elements data
	 */
	getDeletedData() {
		return this.deletedData;
	}

	/**
	 * Gets restored elements
	 */
	getRestoredElements() {
		return this.restoredElements;
	}

	/**
	 * Checks if this command can be merged with another delete command
	 */
	canMergeWith(otherCommand) {
		if (!(otherCommand instanceof DeleteElementCommand)) {
			return false;
		}

		const timeDiff = Math.abs(this.timestamp - otherCommand.timestamp);
		const maxMergeTime = 2000; // 2 seconds

		return timeDiff <= maxMergeTime;
	}

	/**
	 * Merges this command with another compatible delete command
	 */
	mergeWith(otherCommand) {
		if (!this.canMergeWith(otherCommand)) {
			return null;
		}

		const allElements = [...this.elementsToDelete, ...otherCommand.elementsToDelete];
		const mergedCommand = new DeleteElementCommand(
			this.graphService, 
			this.portService, 
			allElements
		);

		mergedCommand.setDescription(`Delete ${allElements.length} items`);
		mergedCommand.setGroupId(this.groupId || this.id);

		return mergedCommand;
	}

	/**
	 * Gets estimated execution time
	 */
	getEstimatedExecutionTime() {
		return this.elementsToDelete.length * 20; // 20ms per element
	}

	/**
	 * Gets memory usage including stored element data
	 */
	getMemoryUsage() {
		const baseSize = super.getMemoryUsage();
		const deletedDataSize = JSON.stringify(this.deletedData).length;
		return baseSize + deletedDataSize;
	}

	/**
	 * Checks if the command should be saved in history
	 */
	shouldSaveInHistory() {
		return this.elementsToDelete.length > 0;
	}

	/**
	 * Performs cleanup when command is removed from history
	 */
	cleanup() {
		super.cleanup();
		this.deletedData = [];
		this.connectedLinks = [];
		this.restoredElements = [];
		this.elementsToDelete = [];
	}

	/**
	 * Serializes command-specific data
	 */
	toJSON() {
		const baseData = super.toJSON();
		
		return {
			...baseData,
			elementsToDeleteIds: this.elementsToDelete.map(el => el.id),
			deletedData: this.deletedData,
			connectedLinkIds: this.connectedLinks.map(link => link.id)
		};
	}

	/**
	 * Restores command from JSON data
	 */
	static fromJSON(data, graphService, portService) {
		const elements = data.elementsToDeleteIds.map(id => graphService.getElementById(id)).filter(Boolean);
		
		const command = new DeleteElementCommand(graphService, portService, elements);
		command.id = data.id;
		command.description = data.description;
		command.executed = data.executed;
		command.timestamp = data.timestamp;
		command.groupId = data.groupId;
		command.metadata = data.metadata || {};
		command.deletedData = data.deletedData || [];

		return command;
	}
}

/**
 * DeleteMultipleCommand - Command for deleting multiple separate groups of elements
 */
export class DeleteMultipleCommand extends BaseCommand {
	constructor(deleteCommands = []) {
		super('Delete multiple groups');
		this.deleteCommands = deleteCommands;
	}

	/**
	 * Validates all sub-commands
	 */
	isValid() {
		return this.deleteCommands.every(command => command.isValid());
	}

	/**
	 * Executes all delete commands
	 */
	execute() {
		try {
			for (const command of this.deleteCommands) {
				const result = command.execute();
				if (result === false) {
					this.undoExecutedCommands();
					return false;
				}
			}

			this.markAsExecuted();
			return true;

		} catch (error) {
			this.undoExecutedCommands();
			console.error('DeleteMultipleCommand: Execution failed:', error);
			return false;
		}
	}

	/**
	 * Undoes all delete commands in reverse order
	 */
	undo() {
		try {
			for (let i = this.deleteCommands.length - 1; i >= 0; i--) {
				const command = this.deleteCommands[i];
				if (command.executed) {
					command.undo();
				}
			}

			this.markAsNotExecuted();
			return true;

		} catch (error) {
			console.error('DeleteMultipleCommand: Undo failed:', error);
			return false;
		}
	}

	/**
	 * Undoes only the successfully executed commands
	 */
	undoExecutedCommands() {
		for (let i = this.deleteCommands.length - 1; i >= 0; i--) {
			const command = this.deleteCommands[i];
			if (command.executed) {
				try {
					command.undo();
				} catch (error) {
					console.error('DeleteMultipleCommand: Error undoing command:', error);
				}
			}
		}
	}

	/**
	 * Gets entities affected by all sub-commands
	 */
	getAffectedEntities() {
		const entities = new Set();
		this.deleteCommands.forEach(command => {
			command.getAffectedEntities().forEach(id => entities.add(id));
		});
		return Array.from(entities);
	}

	/**
	 * Gets estimated execution time for all commands
	 */
	getEstimatedExecutionTime() {
		return this.deleteCommands.reduce((total, cmd) => total + cmd.getEstimatedExecutionTime(), 0);
	}
}