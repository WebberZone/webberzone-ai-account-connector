/**
 * Account sign-in card for Settings → Connectors.
 *
 * ES module, minified with pnpm run build:assets. React and components come from the wp-* classic
 * scripts that the Connectors page already loads; all strings are translated in PHP and passed
 * in through the module data.
 */
import {
	__experimentalRegisterConnector as registerConnector,
	__experimentalConnectorItem as ConnectorItem,
} from '@wordpress/connectors';

const MODULE = 'webberzone-ai-account-connector';

const {
	createElement: el,
	createInterpolateElement,
	useEffect,
	useRef,
	useState,
} = window.wp.element;
const {
	Button,
	CheckboxControl,
	ExternalLink,
	Modal,
	Notice,
	Spinner,
	TextControl,
	__experimentalHStack: HStack,
	__experimentalVStack: VStack,
	__experimentalText: Text,
} = window.wp.components;

const dataElement = document.getElementById( 'wp-script-module-data-' + MODULE );
const { providers = [] } = dataElement ? JSON.parse( dataElement.textContent ) : {};

function registerAccount( config ) {
	const s = config.strings || {};
	const warning = Array.isArray( config.warning ) ? config.warning : [];

	function call( action, fields ) {
		return window
			.fetch( config.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: new URLSearchParams( Object.assign( { action: config.prefix + '_' + action, _ajax_nonce: config.nonce }, fields || {} ) ),
			} )
			.then( ( response ) => response.json() )
			.then( ( result ) => {
				if ( ! result.success ) {
					throw new Error( result.data?.message || result.data || s.requestFailed );
				}
				return result.data;
			} );
	}

	function linked( text, url ) {
		if ( ! url ) {
			return text.replace( /<\/?a>/g, '' );
		}
		return createInterpolateElement( text, { a: el( ExternalLink, { href: url } ) } );
	}

	function Note() {
		return config.note ? el( Text, { as: 'p' }, linked( config.note, config.noteUrl ) ) : null;
	}

	function openConsentWindow() {
		return window.open( 'about:blank', 'wzaiac-signin-' + config.prefix );
	}

	function SignInModal( { onClose, onConnected } ) {
		const popupRef = useRef( null );
		const [ acknowledged, setAcknowledged ] = useState( 0 === warning.length );
		const [ checked, setChecked ] = useState( false );
		const [ authorizeUrl, setAuthorizeUrl ] = useState( '' );
		const [ userCode, setUserCode ] = useState( '' );
		const [ pollSeconds, setPollSeconds ] = useState( 5 );
		const [ blocked, setBlocked ] = useState( false );
		const [ code, setCode ] = useState( '' );
		const [ error, setError ] = useState( null );
		const [ busy, setBusy ] = useState( false );
		const [ ready, setReady ] = useState( false );
		const [ attempt, setAttempt ] = useState( 0 );

		useEffect( () => {
			if ( ! acknowledged ) {
				return undefined;
			}
			let active = true;
			const popupAtStart = popupRef.current;
			setReady( false );
			setError( null );
			setCode( '' );
			setUserCode( '' );
			setBlocked( ! popupAtStart );

			call( 'start', { acknowledged: '1' } )
				.then( ( data ) => {
					if ( ! active ) {
						return;
					}
					setAuthorizeUrl( data.authorize_url );
					setPollSeconds( parseInt( data.interval, 10 ) || 5 );
					setUserCode( data.user_code || '' );
					setReady( true );
					if ( popupAtStart && ! popupAtStart.closed ) {
						popupAtStart.location.href = data.authorize_url;
						setBlocked( false );
					} else {
						setBlocked( true );
					}
				} )
				.catch( ( e ) => {
					if ( ! active ) {
						return;
					}
					if ( popupAtStart && ! popupAtStart.closed ) {
						popupAtStart.close();
					}
					setError( e.message );
				} );

			return () => {
				active = false;
			};
		}, [ acknowledged, attempt ] );

		useEffect( () => {
			if ( ! userCode ) {
				return undefined;
			}
			let active = true;
			let timer;
			const interval = ( pollSeconds + 1 ) * 1000;
			const poll = () => {
				call( 'poll' )
					.then( ( status ) => {
						if ( ! active ) {
							return;
						}
						if ( 'connected' === status ) {
							onConnected();
						} else {
							timer = window.setTimeout( poll, interval );
						}
					} )
					.catch( ( e ) => {
						if ( active ) {
							setUserCode( '' );
							setReady( false );
							setError( e.message );
						}
					} );
			};
			timer = window.setTimeout( poll, interval );
			return () => {
				active = false;
				window.clearTimeout( timer );
			};
		}, [ userCode ] );

		useEffect( () => {
			return () => {
				const popup = popupRef.current;
				if ( ! popup || popup.closed ) {
					return;
				}
				try {
					if ( 'about:blank' === popup.location.href ) {
						popup.close();
					}
				} catch ( err ) {
					// Already on the provider's page, which this window cannot inspect. Leave it open.
				}
			};
		}, [] );

		const close = () => {
			call( 'cancel' ).catch( () => {} );
			onClose();
		};

		const proceed = () => {
			// Opened here, from the click, so the browser does not treat the sign-in page as a blocked pop-up.
			popupRef.current = openConsentWindow();
			setAcknowledged( true );
		};

		const retry = () => {
			popupRef.current = openConsentWindow();
			setAttempt( attempt + 1 );
		};

		const submit = ( event ) => {
			event.preventDefault();
			const pasted = code.trim();
			if ( busy || ! pasted ) {
				return;
			}
			setBusy( true );
			setError( null );
			call( 'exchange', { code: pasted } )
				.then( () => onConnected() )
				.catch( ( e ) => {
					setError( e.message );
					setBusy( false );
				} );
		};

		let body;
		if ( ! acknowledged ) {
			body = el(
				VStack,
				{ spacing: 4 },
				el(
					Notice,
					{ status: 'warning', isDismissible: false },
					el(
						VStack,
						{ spacing: 2 },
						el( Text, { weight: 600 }, s.warningTitle ),
						warning.map( ( paragraph, index ) => el( Text, { as: 'p', key: index }, paragraph ) )
					)
				),
				config.expect && el( Text, { as: 'p' }, config.expect ),
				el( CheckboxControl, {
					label: s.acknowledge,
					checked,
					onChange: setChecked,
					__nextHasNoMarginBottom: true,
				} ),
				el(
					HStack,
					{ justify: 'flex-start' },
					el( Button, { variant: 'primary', disabled: ! checked, onClick: proceed }, s.continue ),
					el( Button, { variant: 'tertiary', onClick: close }, s.cancel )
				)
			);
		} else if ( ! ready && ! error ) {
			body = el( HStack, { justify: 'flex-start' }, el( Spinner ), el( Text, null, s.opening ) );
		} else {
			body = el(
				'form',
				{ onSubmit: submit },
				el(
					VStack,
					{ spacing: 4 },
					el( Note ),
					error && el( Notice, { status: 'error', isDismissible: false }, error ),
					ready && el( Text, { as: 'p' }, linked( s.browserStep, authorizeUrl ) ),
					blocked && el( Text, { as: 'p' }, s.popupBlocked ),
					ready && el( Text, { as: 'p' }, s.browserPaste ),
					userCode &&
						el(
							VStack,
							{ spacing: 2 },
							el( Text, { weight: 600 }, s.codeLabel ),
							el( 'code', { style: { fontSize: '24px', letterSpacing: '0.1em', userSelect: 'all' } }, userCode ),
							el( HStack, { justify: 'flex-start' }, el( Spinner ), el( Text, null, s.waiting ) )
						),
					ready &&
						! userCode &&
						el( TextControl, {
							label: config.pasteLabel,
							value: code,
							onChange: setCode,
							autoComplete: 'off',
							spellCheck: 'false',
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
						} ),
					el(
						HStack,
						{ justify: 'flex-start' },
						ready &&
							! userCode &&
							el(
								Button,
								{ variant: 'primary', type: 'submit', isBusy: busy, disabled: busy || ! code.trim() },
								s.connect
							),
						error && el( Button, { variant: 'secondary', type: 'button', onClick: retry }, s.tryAgain ),
						el( Button, { variant: 'tertiary', type: 'button', onClick: close }, s.cancel )
					)
				)
			);
		}

		return el( Modal, { title: s.signIn, onRequestClose: close, size: 'medium' }, body );
	}

	function AccountConnector( { name, description, logo } ) {
		const [ isOpen, setIsOpen ] = useState( false );
		const [ isBusy, setIsBusy ] = useState( false );
		const [ error, setError ] = useState( null );
		const connected = !! config.connected;

		const disconnect = () => {
			setIsBusy( true );
			setError( null );
			call( 'disconnect' )
				.then( () => window.location.reload() )
				.catch( ( e ) => {
					setError( e.message );
					setIsBusy( false );
				} );
		};

		const actionArea = el(
			HStack,
			{ spacing: 3, expanded: false },
			connected &&
				el(
					Text,
					{ style: { color: 'var(--wp-components-color-accent-darker-10, #008a20)' } },
					s.connected
				),
			connected
				? el(
						Button,
						{ variant: 'tertiary', size: 'compact', isDestructive: true, isBusy, disabled: isBusy, onClick: disconnect },
						s.disconnect
				  )
				: el( Button, { variant: 'secondary', size: 'compact', onClick: () => setIsOpen( true ) }, s.signIn )
		);

		return el(
			ConnectorItem,
			{ className: 'connector-item--' + MODULE, logo, name, description, actionArea },
			connected && config.account ? el( Text, { variant: 'muted', size: 12 }, config.account ) : null,
			config.manageUrl ? el( ExternalLink, { href: config.manageUrl }, s.manage ) : null,
			error && el( Notice, { status: 'error', isDismissible: false }, error ),
			isOpen &&
				el( SignInModal, {
					onClose: () => setIsOpen( false ),
					onConnected: () => window.location.reload(),
				} )
		);
	}

	if ( config.slug ) {
		registerConnector( config.slug, { render: AccountConnector } );
	}
}

providers.forEach( registerAccount );
