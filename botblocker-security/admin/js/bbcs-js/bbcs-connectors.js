import { __experimentalRegisterConnector as registerConnector, __experimentalConnectorItem as ConnectorItem } from '@wordpress/connectors';

const { createElement, useMemo, useState } = window.wp.element;
const { Button, __experimentalHStack: HStack } = window.wp.components;
const { useDispatch, useSelect } = window.wp.data;
const { store: coreStore } = window.wp.coreData;
const { store: noticesStore } = window.wp.notices;
const { __, sprintf } = window.wp.i18n;

const connectorData = window.bbcsConnector || {};

function ConnectedBadge() {
	return createElement(
		'span',
		{
			style: {
				backgroundColor: '#eff8f0',
				borderRadius: '2px',
				color: '#345b37',
				fontSize: '13px',
				fontWeight: 500,
				padding: '4px 12px',
				whiteSpace: 'nowrap',
			},
		},
		__( 'Connected', 'botblocker-security' )
	);
}

function getPluginBasename( plugin ) {
	const file = plugin?.file || connectorData.pluginFile || '';
	return file.replace( /\.php$/, '' );
}

function BotBlockerConnector( { name, description, logo, plugin, authentication } ) {
	const [ isBusy, setIsBusy ] = useState( false );
	const pluginBasename = useMemo( () => getPluginBasename( plugin ), [ plugin ] );
	const proUrl = authentication?.credentialsUrl || connectorData.proUrl || 'admin.php?page=bbcs_cloud_api';
	const isCloudApiActive = !! connectorData.isCloudApiActive;

	const { pluginStatus, canActivatePlugins } = useSelect(
		( select ) => {
			const store = select( coreStore );
			if ( ! pluginBasename ) {
				return {
					pluginStatus: 'active',
					canActivatePlugins: true,
				};
			}

			const pluginRecord = store.getEntityRecord( 'root', 'plugin', pluginBasename );
			const hasFinished = store.hasFinishedResolution( 'getEntityRecord', [ 'root', 'plugin', pluginBasename ] );
			if ( ! hasFinished ) {
				return {
					pluginStatus: 'checking',
					canActivatePlugins: true,
				};
			}

			if ( pluginRecord ) {
				const active = pluginRecord.status === 'active' || pluginRecord.status === 'network-active';
				return {
					pluginStatus: active ? 'active' : 'inactive',
					canActivatePlugins: true,
				};
			}

			return {
				pluginStatus: plugin?.isActivated ? 'active' : plugin?.isInstalled ? 'inactive' : 'not-installed',
				canActivatePlugins: false,
			};
		},
		[ pluginBasename, plugin?.isActivated, plugin?.isInstalled ]
	);

	const { saveEntityRecord } = useDispatch( coreStore );
	const { createSuccessNotice, createErrorNotice } = useDispatch( noticesStore );

	const activatePlugin = async () => {
		if ( ! pluginBasename || ! canActivatePlugins || isBusy ) {
			return;
		}

		setIsBusy( true );
		try {
			await saveEntityRecord(
				'root',
				'plugin',
				{
					plugin: pluginBasename,
					status: 'active',
				},
				{ throwOnError: true }
			);
			createSuccessNotice(
				sprintf( __( '%s activated successfully.', 'botblocker-security' ), name ),
				{
					id: 'botblocker-connector-activate-success',
					type: 'snackbar',
				}
			);
		} catch ( error ) {
			createErrorNotice(
				sprintf( __( 'Failed to activate %s.', 'botblocker-security' ), name ),
				{
					id: 'botblocker-connector-activate-error',
					type: 'snackbar',
				}
			);
		} finally {
			setIsBusy( false );
		}
	};

	let actionArea;
	if ( pluginStatus === 'active' ) {
		actionArea = createElement(
			HStack,
			{ spacing: 3, expanded: false },
			isCloudApiActive && createElement( ConnectedBadge ),
			createElement(
				Button,
				{
					href: proUrl,
					size: 'compact',
					variant: isCloudApiActive ? 'tertiary' : 'secondary',
				},
				isCloudApiActive ? __( 'Manage PRO', 'botblocker-security' ) : __( 'Buy PRO', 'botblocker-security' )
			)
		);
	} else {
		actionArea = createElement(
			Button,
			{
				accessibleWhenDisabled: true,
				disabled: pluginStatus === 'checking' || ! canActivatePlugins || isBusy,
				isBusy,
				onClick: activatePlugin,
				size: 'compact',
				variant: 'secondary',
			},
			isBusy ? __( 'Activating...', 'botblocker-security' ) : pluginStatus === 'checking' ? __( 'Checking...', 'botblocker-security' ) : __( 'Activate', 'botblocker-security' )
		);
	}

	return createElement( ConnectorItem, {
		className: 'connector-item--botblocker-security',
		logo,
		name,
		description,
		actionArea,
	} );
}

registerConnector( 'botblocker', {
	render: BotBlockerConnector,
} );