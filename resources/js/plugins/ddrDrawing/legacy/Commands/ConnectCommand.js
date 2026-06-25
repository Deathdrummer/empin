import { BaseCommand } from './BaseCommand.js';

/**
 * ConnectCommand - Command for creating connections between elements
 */
export class ConnectCommand extends BaseCommand {
	constructor(graphService, portService, validationService, options = {}) {
		const {
			sourceElement,
			targetElement,
			sourcePortId = null,
			targetPortId = null,
			linkType = 'CustomLink',
			attrs = {},
			router = { name: 'manhattan' },
			connector = { name: 'rounded' },
			labels = []
		} = options;

		super('Create connection');
		
		this.graphService = graphService;
		this.portService = portService;
		this.validationService = validationService;
		this.sourceElement = sourceElement;
		this.targetElement = targetElement;
		this.sourcePortId = sourcePortId;
		this.targetPortId = targetPortId;
		this.linkType = linkType;
		this.attrs = attrs;
		this.router = router;
		this.connector = connector;
		this.labels = labels;
		this.createdLink = null;
		this.autoSelectPorts = options.autoSelectPorts !== false;
	}

	/**
	 * Validates command parameters before execution
	 */
	validateParameters() {
		const errors = [];

		if (!this.sourceElement || typeof this.sourceElement.isElement !== 'function') {
			errors.push('Invalid source element specified');
		}

		if (!this.targetElement || typeof this.targetElement.isElement !== 'function') {
			errors.push('Invalid target element specified');
		}

		if (this.sourceElement === this.targetElement) {
			errors.push('Cannot connect element to itself');
		}

		if (!this.linkType || typeof this.linkType !== 'string') {
			errors.push('Link type must be specified');
		}

		return {
			valid: errors.length === 0,
			errors
		};
	}

	/**
	 * Automatically selects available ports for connection
	 */
	selectPortsAutomatically() {
		if (this.sourcePortId && this.targetPortId) {
			return { sourcePortId: this.sourcePortId, targetPortId: this.targetPortId };
		}

		let selectedSourcePort = this.sourcePortId;
		let selectedTargetPort = this.targetPortId;

		if (!selectedSourcePort) {
			const sourceFreePorts = this.portService.getFreePorts(this.sourceElement);
			if (sourceFreePorts.length > 0) {
				selectedSourcePort = sourceFreePorts[0].id;
			}
		}

		if (!selectedTargetPort) {
			const targetFreePorts = this.portService.getFreePorts(this.targetElement);
			if (targetFreePorts.length > 0) {
				selectedTargetPort = targetFreePorts[0].id;
			}
		}

		return {
			sourcePortId: selectedSourcePort,
			targetPortId: selectedTargetPort
		};
	}

	/**
	 * Validates connection with business rules
	 */
	isValid() {
		const paramValidation = this.validateParameters();
		if (!paramValidation.valid) {
			return false;
		}

		let sourcePortId = this.sourcePortId;
		let targetPortId = this.targetPortId;

		if (this.autoSelectPorts) {
			const selectedPorts = this.selectPortsAutomatically();
			sourcePortId = selectedPorts.sourcePortId;
			targetPortId = selectedPorts.targetPortId;
		}

		if (!sourcePortId || !targetPortId) {
			return false;
		}

		const validation = this.validationService.validateConnection(
			{ cellView: { model: this.sourceElement }, magnet: { getAttribute: () => sourcePortId } },
			{ cellView: { model: this.targetElement }, magnet: { getAttribute: () => targetPortId } }
		);

		return validation.valid;
	}

	/**
	 * Creates link configuration object
	 */
	createLinkConfiguration() {
		const selectedPorts = this.autoSelectPorts ? this.selectPortsAutomatically() : {
			sourcePortId: this.sourcePortId,
			targetPortId: this.targetPortId
		};

		return {
			source: {
				id: this.sourceElement.id,
				port: selectedPorts.sourcePortId
			},
			target: {
				id: this.targetElement.id,
				port: selectedPorts.targetPortId
			},
			attrs: {
				line: {
					stroke: '#8a8a96',
					strokeWidth: 2,
					targetMarker: { type: 'none' },
					...this.attrs
				}
			},
			router: this.router,
			connector: this.connector,
			labels: this.labels
		};
	}

	/**
	 * Executes the connect command
	 */
	execute() {
		try {
			if (!this.isValid()) {
				return false;
			}

			const linkConfig = this.createLinkConfiguration();
			this.createdLink = this.graphService.createLink(linkConfig);

			if (!this.createdLink) {
				return false;
			}

			this.portService.occupyPort(this.sourceElement, linkConfig.source.port, this.createdLink.id);
			this.portService.occupyPort(this.targetElement, linkConfig.target.port, this.createdLink.id);

			this.setMetadata('linkId', this.createdLink.id);
			this.setMetadata('sourceElementId', this.sourceElement.id);
			this.setMetadata('targetElementId', this.targetElement.id);
			this.setMetadata('sourcePortId', linkConfig.source.port);
			this.setMetadata('targetPortId', linkConfig.target.port);
			this.markAsExecuted();

			return this.createdLink;

		} catch (error) {
			console.error('ConnectCommand: Execution failed:', error);
			return false;
		}
	}

	/**
	 * Undoes the connect command
	 */
	undo() {
		try {
			if (!this.executed || !this.createdLink) {
				return false;
			}

			const sourcePortId = this.getMetadata('sourcePortId');
			const targetPortId = this.getMetadata('targetPortId');

			this.portService.freePort(this.sourceElement, sourcePortId);
			this.portService.freePort(this.targetElement, targetPortId);

			const linkId = this.createdLink.id;
			this.createdLink.remove();
			this.createdLink = null;

			this.setMetadata('removedLinkId', linkId);
			this.markAsNotExecuted();

			return true;

		} catch (error) {
			console.error('ConnectCommand: Undo failed:', error);
			return false;
		}
	}

	/**
	 * Redoes the connect command
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
		const entities = [this.sourceElement.id, this.targetElement.id];
		
		if (this.createdLink) {
			entities.push(this.createdLink.id);
		}

		const linkId = this.getMetadata('linkId') || this.getMetadata('removedLinkId');
		if (linkId && !entities.includes(linkId)) {
			entities.push(linkId);
		}

		return entities;
	}

	/**
	 * Checks if command affects specific entities
	 */
	affectsEntities(entityIds) {
		const affectedIds = this.getAffectedEntities();
		return affectedIds.some(id => entityIds.includes(id));
	}

	/**
	 * Gets the created link instance
	 */
	getCreatedLink() {
		return this.createdLink;
	}

	/**
	 * Gets connection information
	 */
	getConnectionInfo() {
		return {
			sourceElement: this.sourceElement,
			targetElement: this.targetElement,
			sourcePortId: this.getMetadata('sourcePortId'),
			targetPortId: this.getMetadata('targetPortId'),
			linkId: this.getMetadata('linkId')
		};
	}

	/**
	 * Checks if this command can be merged with another connect command
	 */
	canMergeWith(otherCommand) {
		if (!(otherCommand instanceof ConnectCommand)) {
			return false;
		}

		const timeDiff = Math.abs(this.timestamp - otherCommand.timestamp);
		const maxMergeTime = 1000; // 1 second

		if (timeDiff > maxMergeTime) {
			return false;
		}

		return (this.sourceElement === otherCommand.sourceElement ||
				this.targetElement === otherCommand.targetElement ||
				this.sourceElement === otherCommand.targetElement ||
				this.targetElement === otherCommand.sourceElement);
	}

	/**
	 * Merges this command with another compatible connect command
	 */
	mergeWith(otherCommand) {
		if (!this.canMergeWith(otherCommand)) {
			return null;
		}

		const mergedCommand = new ConnectMultipleCommand([this, otherCommand]);
		mergedCommand.setDescription('Create multiple connections');
		mergedCommand.setGroupId(this.groupId || this.id);

		return mergedCommand;
	}

	/**
	 * Gets estimated execution time
	 */
	getEstimatedExecutionTime() {
		return 30; // 30ms estimated for connection creation
	}

	/**
	 * Serializes command-specific data
	 */
	toJSON() {
		const baseData = super.toJSON();
		
		return {
			...baseData,
			sourceElementId: this.sourceElement.id,
			targetElementId: this.targetElement.id,
			sourcePortId: this.sourcePortId,
			targetPortId: this.targetPortId,
			linkType: this.linkType,
			attrs: this.attrs,
			router: this.router,
			connector: this.connector,
			labels: this.labels,
			autoSelectPorts: this.autoSelectPorts,
			createdLinkId: this.createdLink ? this.createdLink.id : null
		};
	}

	/**
	 * Restores command from JSON data
	 */
	static fromJSON(data, graphService, portService, validationService) {
		const sourceElement = graphService.getElementById(data.sourceElementId);
		const targetElement = graphService.getElementById(data.targetElementId);

		if (!sourceElement || !targetElement) {
			throw new Error('ConnectCommand: Source or target element not found');
		}

		const command = new ConnectCommand(graphService, portService, validationService, {
			sourceElement,
			targetElement,
			sourcePortId: data.sourcePortId,
			targetPortId: data.targetPortId,
			linkType: data.linkType,
			attrs: data.attrs,
			router: data.router,
			connector: data.connector,
			labels: data.labels,
			autoSelectPorts: data.autoSelectPorts
		});

		command.id = data.id;
		command.description = data.description;
		command.executed = data.executed;
		command.timestamp = data.timestamp;
		command.groupId = data.groupId;
		command.metadata = data.metadata || {};

		if (data.createdLinkId && data.executed) {
			command.createdLink = graphService.getElementById(data.createdLinkId);
		}

		return command;
	}
}

/**
 * ConnectMultipleCommand - Command for creating multiple connections as a batch
 */
export class ConnectMultipleCommand extends BaseCommand {
	constructor(connectCommands = []) {
		super('Create multiple connections');
		this.connectCommands = connectCommands;
		this.createdLinks = [];
	}

	/**
	 * Validates all sub-commands
	 */
	isValid() {
		return this.connectCommands.every(command => command.isValid());
	}

	/**
	 * Executes all connect commands
	 */
	execute() {
		try {
			this.createdLinks = [];

			for (const command of this.connectCommands) {
				const result = command.execute();
				if (result === false) {
					this.undoCreatedConnections();
					return false;
				}
				this.createdLinks.push(command.getCreatedLink());
			}

			this.markAsExecuted();
			return this.createdLinks;

		} catch (error) {
			this.undoCreatedConnections();
			console.error('ConnectMultipleCommand: Execution failed:', error);
			return false;
		}
	}

	/**
	 * Undoes all created connections
	 */
	undo() {
		try {
			for (let i = this.connectCommands.length - 1; i >= 0; i--) {
				const command = this.connectCommands[i];
				if (command.executed) {
					command.undo();
				}
			}

			this.createdLinks = [];
			this.markAsNotExecuted();
			return true;

		} catch (error) {
			console.error('ConnectMultipleCommand: Undo failed:', error);
			return false;
		}
	}

	/**
	 * Undoes only the successfully created connections
	 */
	undoCreatedConnections() {
		for (let i = this.connectCommands.length - 1; i >= 0; i--) {
			const command = this.connectCommands[i];
			if (command.executed) {
				try {
					command.undo();
				} catch (error) {
					console.error('ConnectMultipleCommand: Error undoing connection:', error);
				}
			}
		}
		this.createdLinks = [];
	}

	/**
	 * Gets all created links
	 */
	getCreatedLinks() {
		return this.createdLinks;
	}

	/**
	 * Gets entities affected by this command
	 */
	getAffectedEntities() {
		const entities = new Set();
		this.connectCommands.forEach(command => {
			command.getAffectedEntities().forEach(id => entities.add(id));
		});
		return Array.from(entities);
	}

	/**
	 * Adds another connect command to the batch
	 */
	addCommand(connectCommand) {
		if (connectCommand instanceof ConnectCommand) {
			this.connectCommands.push(connectCommand);
			this.setDescription(`Create ${this.connectCommands.length} connections`);
		}
	}

	/**
	 * Gets estimated execution time for all commands
	 */
	getEstimatedExecutionTime() {
		return this.connectCommands.reduce((total, cmd) => total + cmd.getEstimatedExecutionTime(), 0);
	}
}

/**
 * DisconnectCommand - Command for removing connections between elements
 */
export class DisconnectCommand extends BaseCommand {
	constructor(graphService, portService, link) {
		super('Remove connection');
		
		this.graphService = graphService;
		this.portService = portService;
		this.link = link;
		this.linkData = null;
		this.restoredLink = null;
	}

	/**
	 * Validates command parameters
	 */
	validateParameters() {
		const errors = [];

		if (!this.link || !this.link.isLink()) {
			errors.push('Invalid link specified for disconnection');
		}

		return {
			valid: errors.length === 0,
			errors
		};
	}

	/**
	 * Validates disconnection
	 */
	isValid() {
		const paramValidation = this.validateParameters();
		if (!paramValidation.valid) {
			return false;
		}

		return this.graphService.graph.getCells().includes(this.link);
	}

	/**
	 * Stores link data for restoration
	 */
	storeLinkData() {
		this.linkData = {
			id: this.link.id,
			type: this.link.get('type'),
			source: this.link.get('source'),
			target: this.link.get('target'),
			attrs: this.link.get('attrs'),
			router: this.link.get('router'),
			connector: this.link.get('connector'),
			labels: this.link.get('labels'),
			serializedData: this.link.toJSON()
		};
	}

	/**
	 * Executes the disconnect command
	 */
	execute() {
		try {
			if (!this.isValid()) {
				return false;
			}

			this.storeLinkData();

			const sourceElement = this.link.getSourceElement();
			const targetElement = this.link.getTargetElement();
			const sourcePort = this.link.get('source').port;
			const targetPort = this.link.get('target').port;

			if (sourceElement && sourcePort) {
				this.portService.freePort(sourceElement, sourcePort);
			}

			if (targetElement && targetPort) {
				this.portService.freePort(targetElement, targetPort);
			}

			this.link.remove();

			this.setMetadata('linkId', this.linkData.id);
			this.setMetadata('sourceElementId', sourceElement ? sourceElement.id : null);
			this.setMetadata('targetElementId', targetElement ? targetElement.id : null);
			this.markAsExecuted();

			return true;

		} catch (error) {
			console.error('DisconnectCommand: Execution failed:', error);
			return false;
		}
	}

	/**
	 * Undoes the disconnect command
	 */
	undo() {
		try {
			if (!this.executed || !this.linkData) {
				return false;
			}

			const cellNamespace = this.graphService.cellNamespace;
			this.restoredLink = new cellNamespace.CustomLink();
			this.restoredLink.set(this.linkData.serializedData);
			this.restoredLink.addTo(this.graphService.graph);

			const sourceElement = this.restoredLink.getSourceElement();
			const targetElement = this.restoredLink.getTargetElement();
			const sourcePort = this.restoredLink.get('source').port;
			const targetPort = this.restoredLink.get('target').port;

			if (sourceElement && sourcePort) {
				this.portService.occupyPort(sourceElement, sourcePort, this.restoredLink.id);
			}

			if (targetElement && targetPort) {
				this.portService.occupyPort(targetElement, targetPort, this.restoredLink.id);
			}

			this.markAsNotExecuted();
			return true;

		} catch (error) {
			console.error('DisconnectCommand: Undo failed:', error);
			return false;
		}
	}

	/**
	 * Gets entities affected by this command
	 */
	getAffectedEntities() {
		const entities = [];
		
		if (this.linkData) {
			entities.push(this.linkData.id);
			if (this.linkData.source.id) entities.push(this.linkData.source.id);
			if (this.linkData.target.id) entities.push(this.linkData.target.id);
		} else {
			entities.push(this.link.id);
			const sourceElement = this.link.getSourceElement();
			const targetElement = this.link.getTargetElement();
			if (sourceElement) entities.push(sourceElement.id);
			if (targetElement) entities.push(targetElement.id);
		}

		return entities;
	}

	/**
	 * Gets estimated execution time
	 */
	getEstimatedExecutionTime() {
		return 20; // 20ms estimated for disconnection
	}
}