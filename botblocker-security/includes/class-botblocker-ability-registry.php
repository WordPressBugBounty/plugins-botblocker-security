<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BotBlockerAbilityRegistry' ) ) {

	final class BotBlockerAbilityRegistry {

		/** @var array<string,array>|null */
		private static $allCache = null;

		public static function flush(): void {
			self::$allCache = null;
		}

		/**
		 * Every known ability: core definitions first, then manifest-declared
		 * add-on abilities that do not collide with a core name.
		 *
		 * @return array<string,array>
		 */
		public static function all(): array {
			if ( self::$allCache !== null ) {
				return self::$allCache;
			}

			$all = array();
			if ( function_exists( 'bbcs_get_wp_ability_definitions' ) ) {
				foreach ( bbcs_get_wp_ability_definitions() as $definition ) {
					if ( ! is_array( $definition ) || empty( $definition['name'] ) ) {
						continue;
					}
					$definition['owner'] = '';
					if ( empty( $definition['risk'] ) ) {
						$definition['risk'] = ! empty( $definition['annotations']['readonly'] ) ? 'read' : 'manage';
					}
					if ( ! isset( $definition['pii'] ) ) {
						$definition['pii'] = false;
					}
					if ( ! isset( $definition['public'] ) ) {
						$definition['public'] = bbcs_ability_public_flag( $definition );
					}
					$all[ $definition['name'] ] = $definition;
				}
			}

			foreach ( self::addonEntries( false ) as $name => $definition ) {
				if ( isset( $all[ $name ] ) ) {
					continue;
				}
				$all[ $name ] = $definition;
			}

			self::$allCache = $all;
			return $all;
		}

		/**
		 * @return array<string,array>
		 */
		public static function core(): array {
			$core = array();
			foreach ( self::all() as $name => $definition ) {
				if ( ( $definition['owner'] ?? '' ) === '' ) {
					$core[ $name ] = $definition;
				}
			}
			return $core;
		}

		/**
		 * Add-on abilities that are safe to register right now: active,
		 * compatible add-on, enabled toggle, existing callables.
		 *
		 * @return array<string,array>
		 */
		public static function registerFromManifests(): array {
			$result = array();
			foreach ( self::addonEntries( false ) as $name => $definition ) {
				if ( ! self::isEnabled( $name ) ) {
					continue;
				}
				if ( empty( $definition['callables_ok'] ) ) {
					continue;
				}
				$result[ $name ] = $definition;
			}
			return $result;
		}

		public static function get( string $name ): array {
			$all = self::all();
			return isset( $all[ $name ] ) && is_array( $all[ $name ] ) ? $all[ $name ] : array();
		}

		public static function isEnabled( string $name ): bool {
			$definition = self::get( $name );
			if ( empty( $definition ) ) {
				return false;
			}

			if ( ( $definition['owner'] ?? '' ) === '' ) {
				return function_exists( 'bbcs_wp_ability_allowed' ) && bbcs_wp_ability_allowed( (string) $definition['setting_key'] );
			}

			if ( ! function_exists( 'bbcs_wp_ability_allowed' ) || ! bbcs_wp_ability_allowed( 'bbcs_wp_abilities_enabled' ) ) {
				return false;
			}
			if ( empty( $definition['active'] ) || empty( $definition['compatible'] ) || empty( $definition['callables_ok'] ) ) {
				return false;
			}
			return ! empty( $definition['enabled'] );
		}

		public static function isAllowed( string $name ): bool {
			$definition = self::get( $name );
			if ( empty( $definition ) || ! function_exists( 'bbcs_wp_ability_user_allowed' ) ) {
				return false;
			}
			return bbcs_wp_ability_user_allowed( $name, (string) ( $definition['risk'] ?? 'manage' ) );
		}

		/**
		 * Data for the Integrations tab. Installed add-ons with a non-empty
		 * `abilities` manifest key, including inactive ones.
		 *
		 * @return array<int,array>
		 */
		public static function getAddonGroups(): array {
			if ( ! class_exists( 'BotBlockerAddons' ) ) {
				return array();
			}

			$addons = BotBlockerAddons::scanAll();
			if ( ! is_array( $addons ) ) {
				return array();
			}
			ksort( $addons );

			$groups = array();
			foreach ( $addons as $slug => $addon ) {
				if ( empty( $addon['valid'] ) || empty( $addon['abilities'] ) || ! is_array( $addon['abilities'] ) ) {
					continue;
				}
				$active = BotBlockerAddons::isActive( (string) $slug );
				$list   = array();
				foreach ( $addon['abilities'] as $entry ) {
					$name          = (string) ( $entry['name'] ?? '' );
					$definition    = self::get( $name );
					if ( ! empty( $definition ) && ( $definition['owner'] ?? '' ) !== (string) $slug ) {
						continue;
					}
					$list[]        = array(
						'name'        => $name,
						'label'       => (string) ( $definition['label'] ?? ( $entry['label'] ?? $name ) ),
						'setting_key' => (string) ( $entry['setting_key'] ?? '' ),
						'enabled'     => $active && self::isEnabled( $name ),
					);
				}
				$groups[] = array(
					'slug'      => (string) $slug,
					'name'      => (string) ( $addon['name'] ?? $slug ),
					'active'    => $active,
					'abilities' => $list,
				);
			}
			return $groups;
		}

		/**
		 * @return array<string,array>
		 */
		private static function addonEntries( bool $activeOnly ): array {
			if ( ! class_exists( 'BotBlockerAddons' ) ) {
				return array();
			}

			$addons = BotBlockerAddons::scanAll();
			if ( ! is_array( $addons ) ) {
				return array();
			}
			ksort( $addons );

			$entries = array();
			foreach ( $addons as $slug => $addon ) {
				if ( empty( $addon['valid'] ) || empty( $addon['abilities'] ) || ! is_array( $addon['abilities'] ) ) {
					continue;
				}
				$slug       = (string) $slug;
				$active     = BotBlockerAddons::isActive( $slug );
				$compatible = method_exists( 'BotBlockerAddons', 'isCompatible' ) ? BotBlockerAddons::isCompatible( $addon ) : true;
				if ( $activeOnly && ( ! $active || ! $compatible ) ) {
					continue;
				}
				$option = (string) ( $addon['settings_option'] ?? '' );
				$saved  = $option !== '' && class_exists( 'BotBlockerMultisite' ) ? BotBlockerMultisite::getOption( $option, array() ) : array();
				$saved  = is_array( $saved ) ? $saved : array();

				foreach ( $addon['abilities'] as $entry ) {
					if ( ! is_array( $entry ) || empty( $entry['name'] ) || empty( $entry['setting_key'] ) ) {
						continue;
					}
					$name = (string) $entry['name'];
					if ( isset( $entries[ $name ] ) ) {
						continue;
					}

					$definition = array();
					if ( ! empty( $entry['definition'] ) && is_callable( $entry['definition'] ) ) {
						try {
							$resolved = call_user_func( $entry['definition'] );
						} catch ( Throwable $definition_error ) {
							if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
								// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
								error_log( '[BBCS DEBUG] [Abilities] definition of "' . $name . '" threw and the ability was skipped: ' . get_class( $definition_error ) );
							}
							continue;
						}
						if ( is_array( $resolved ) ) {
							$definition = $resolved;
						}
					}

					$risk = isset( $entry['risk'] ) ? (string) $entry['risk'] : 'read';
					if ( ! in_array( $risk, array( 'read', 'manage', 'danger' ), true ) ) {
						$risk = 'read';
					}

					$execute_ok    = ! empty( $entry['execute'] ) && is_callable( $entry['execute'] );
					$definition_ok = empty( $entry['definition'] ) || is_callable( $entry['definition'] );
					$permission_ok = empty( $entry['permission'] ) || is_callable( $entry['permission'] );

					$annotations = isset( $definition['annotations'] ) && is_array( $definition['annotations'] )
						? $definition['annotations']
						: array(
							'readonly'    => 'read' === $risk,
							'destructive' => 'read' !== $risk,
							'idempotent'  => 'read' === $risk,
						);

					$public = ! empty( $entry['pii'] ) ? false : (bool) ( $entry['public'] ?? false );

					$entries[ $name ] = array(
						'name'                => $name,
						'setting_key'         => (string) $entry['setting_key'],
						'label'               => (string) ( $definition['label'] ?? ( $entry['label'] ?? $name ) ),
						'description'         => (string) ( $definition['description'] ?? '' ),
						'annotations'         => $annotations,
						'input_schema'        => isset( $definition['input_schema'] ) && is_array( $definition['input_schema'] ) ? $definition['input_schema'] : array(),
						'output_schema'       => isset( $definition['output_schema'] ) && is_array( $definition['output_schema'] ) ? $definition['output_schema'] : array( 'type' => 'object' ),
						'execute'             => (string) ( $entry['execute'] ?? '' ),
						'permission_callback' => (string) ( $entry['permission'] ?? '' ),
						'show_in_rest'        => true,
						'public'              => $public,
						'pii'                 => ! empty( $entry['pii'] ),
						'risk'                => $risk,
						'owner'               => $slug,
						'capability'          => isset( $definition['capability'] ) && is_string( $definition['capability'] ) ? $definition['capability'] : '',
						'active'              => $active,
						'compatible'          => $compatible,
						'enabled'             => $option !== '' && $active && $compatible && ! empty( $saved[ $entry['setting_key'] ] ),
						'callables_ok'        => $execute_ok && $definition_ok && $permission_ok,
					);
				}
			}

			return $entries;
		}
	}
}
