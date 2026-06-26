<?php
/**
 * Plugin Name: Allure MCP Bridge
 * Description: Rotas REST p/ o MCP escrever blocos Elementor (_elementor_data), cores e fontes globais (Kit) + registrar a fonte Aspekta, com regeneração de CSS. Ambiente: staging Yuza.
 * Version: 0.6.0
 * Author: Allure
 *
 * Instalar como mu-plugin: subir este arquivo para wp-content/mu-plugins/
 * (cria a pasta se não existir). Ou colar via WPCode/Code Snippets como PHP snippet "run everywhere".
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'rest_api_init', function () {

	$perm = function () {
		// MCP roda como user_id 1 (admin) via o endpoint streamable.
		return current_user_can( 'manage_options' );
	};

	// Ping: confirma que o bridge está carregado e qual versão do Elementor.
	register_rest_route( 'allure/v1', '/ping', array(
		'methods'             => 'GET',
		'permission_callback' => $perm,
		'callback'            => function () {
			return array(
				'ok'              => true,
					'bridge'          => '0.6.0',
				'elementor'       => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : null,
				'elementor_pro'   => defined( 'ELEMENTOR_PRO_VERSION' ) ? ELEMENTOR_PRO_VERSION : null,
				'active_kit'      => (int) get_option( 'elementor_active_kit' ),
				'aspekta'         => allure_aspekta_info(),
			);
		},
	) );

	// Ler de volta o _elementor_data gravado (prova de escrita + leitura de blocos).
	register_rest_route( 'allure/v1', '/block/(?P<id>\d+)', array(
		'methods'             => 'GET',
		'permission_callback' => $perm,
		'callback'            => function ( WP_REST_Request $req ) {
			$id = (int) $req['id'];
			$raw = get_post_meta( $id, '_elementor_data', true );
			$decoded = is_string( $raw ) ? json_decode( $raw, true ) : null;
			return array(
				'ok'           => true,
				'id'           => $id,
				'data_len'     => is_string( $raw ) ? strlen( $raw ) : 0,
				'edit_mode'    => get_post_meta( $id, '_elementor_edit_mode', true ),
				'tpl_type'     => get_post_meta( $id, '_elementor_template_type', true ),
				'el_version'   => get_post_meta( $id, '_elementor_version', true ),
				'data'         => $decoded,
			);
		},
	) );

	// Subir / atualizar um bloco (elementor_library) OU uma página (post_type=page).
	// Body: { title, type, content:[...], page_settings:[], id?:int, post_type? }
	// post_type: "elementor_library" (default) | "page". Em page, template_type = wp-page.
	register_rest_route( 'allure/v1', '/import-block', array(
		'methods'             => 'POST',
		'permission_callback' => $perm,
		'callback'            => function ( WP_REST_Request $req ) {
			$body = $req->get_params(); // mescla JSON + body + query (MCP injeta como body params)
			if ( empty( $body['content'] ) || ! is_array( $body['content'] ) ) {
				return new WP_Error( 'allure_no_content', 'Faltou o array "content" do template.', array( 'status' => 400 ) );
			}

			$allowed_pt = array( 'elementor_library', 'page' );
			$post_type  = isset( $body['post_type'] ) ? sanitize_key( $body['post_type'] ) : 'elementor_library';
			if ( ! in_array( $post_type, $allowed_pt, true ) ) { $post_type = 'elementor_library'; }
			$is_library = ( 'elementor_library' === $post_type );

			$title = isset( $body['title'] ) ? sanitize_text_field( $body['title'] ) : 'Bloco Allure';
			// Numa página o template_type do Elementor é sempre wp-page; na biblioteca usa o "type".
			$tpl_type = $is_library ? ( isset( $body['type'] ) ? sanitize_text_field( $body['type'] ) : 'container' ) : 'wp-page';
			$data_json = wp_json_encode( $body['content'] ); // _elementor_data = só o array content

			$postarr = array(
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'post_title'  => $title,
			);
			if ( ! empty( $body['id'] ) ) {
				$postarr['ID'] = (int) $body['id'];
			}

			$post_id = ! empty( $postarr['ID'] ) ? wp_update_post( $postarr, true ) : wp_insert_post( $postarr, true );
			if ( is_wp_error( $post_id ) ) { return $post_id; }

			// slashes: o WP faz wp_unslash no meta; o JSON do Elementor exige barras preservadas.
			update_post_meta( $post_id, '_elementor_data', wp_slash( $data_json ) );
			update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
			update_post_meta( $post_id, '_elementor_template_type', $tpl_type );
			if ( defined( 'ELEMENTOR_VERSION' ) ) {
				update_post_meta( $post_id, '_elementor_version', ELEMENTOR_VERSION );
			}
			if ( isset( $body['page_settings'] ) ) {
				update_post_meta( $post_id, '_elementor_page_settings', $body['page_settings'] );
			}
			// A taxonomia de tipo só existe na biblioteca; numa página não se aplica.
			if ( $is_library ) {
				wp_set_object_terms( $post_id, $tpl_type, 'elementor_library_type' );
			}

			// Regenera CSS deste documento.
			if ( class_exists( '\Elementor\Plugin' ) ) {
				\Elementor\Plugin::$instance->files_manager->clear_cache();
			}

			return array(
				'ok'        => true,
				'id'        => $post_id,
				'post_type' => $post_type,
				'edit_url'  => admin_url( 'post.php?post=' . $post_id . '&action=elementor' ),
				'view_url'  => get_permalink( $post_id ),
				'type'      => $tpl_type,
			);
		},
	) );

	// Escrever cores globais no Kit ativo (regenera CSS via save do Kit).
	// Body: { system_colors:[{_id,title,color}], custom_colors:[{_id,title,color}] }
	// _id dos system: primary | secondary | text | accent
	register_rest_route( 'allure/v1', '/set-global-colors', array(
		'methods'             => 'POST',
		'permission_callback' => $perm,
		'callback'            => function ( WP_REST_Request $req ) {
			if ( ! class_exists( '\Elementor\Plugin' ) ) {
				return new WP_Error( 'allure_no_elementor', 'Elementor não está ativo.', array( 'status' => 500 ) );
			}
			$body = $req->get_params(); // mescla JSON + body + query (MCP injeta como body params)
			$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit_for_frontend();
			if ( ! $kit ) {
				return new WP_Error( 'allure_no_kit', 'Kit ativo não encontrado.', array( 'status' => 500 ) );
			}

			$settings = array();
			if ( ! empty( $body['system_colors'] ) && is_array( $body['system_colors'] ) ) {
				$settings['system_colors'] = $body['system_colors'];
			}
			if ( ! empty( $body['custom_colors'] ) && is_array( $body['custom_colors'] ) ) {
				$settings['custom_colors'] = $body['custom_colors'];
			}
			if ( empty( $settings ) ) {
				return new WP_Error( 'allure_no_colors', 'Envie system_colors e/ou custom_colors.', array( 'status' => 400 ) );
			}

			allure_kit_save_merged( $kit, $settings ); // merge p/ não apagar outras settings + regen CSS

			return array(
				'ok'       => true,
				'kit_id'   => $kit->get_id(),
				'applied'  => array_keys( $settings ),
			);
		},
	) );

	// Escrever fontes globais no Kit ativo (regenera CSS via save do Kit).
	// Body: { system_typography:[{_id,title,typography_typography:"custom",typography_font_family,typography_font_weight,...}], custom_typography:[...] }
	// _id dos system: primary | secondary | text | accent
	register_rest_route( 'allure/v1', '/set-global-typography', array(
		'methods'             => 'POST',
		'permission_callback' => $perm,
		'callback'            => function ( WP_REST_Request $req ) {
			if ( ! class_exists( '\Elementor\Plugin' ) ) {
				return new WP_Error( 'allure_no_elementor', 'Elementor não está ativo.', array( 'status' => 500 ) );
			}
			$body = $req->get_params(); // mescla JSON + body + query (MCP injeta como body params)
			$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit_for_frontend();
			if ( ! $kit ) {
				return new WP_Error( 'allure_no_kit', 'Kit ativo não encontrado.', array( 'status' => 500 ) );
			}

			$settings = array();
			if ( ! empty( $body['system_typography'] ) && is_array( $body['system_typography'] ) ) {
				$settings['system_typography'] = $body['system_typography'];
			}
			if ( ! empty( $body['custom_typography'] ) && is_array( $body['custom_typography'] ) ) {
				$settings['custom_typography'] = $body['custom_typography'];
			}
			if ( empty( $settings ) ) {
				return new WP_Error( 'allure_no_typography', 'Envie system_typography e/ou custom_typography.', array( 'status' => 400 ) );
			}

			allure_kit_save_merged( $kit, $settings ); // merge p/ não apagar outras settings

			return array(
				'ok'       => true,
				'kit_id'   => $kit->get_id(),
				'applied'  => array_keys( $settings ),
			);
		},
	) );

	// Ler as settings do Kit ativo (descobrir as chaves reais do Theme Style etc).
	register_rest_route( 'allure/v1', '/kit-settings', array(
		'methods'             => 'GET',
		'permission_callback' => $perm,
		'callback'            => function () {
			$kit_id = (int) get_option( 'elementor_active_kit' );
			$raw = get_post_meta( $kit_id, '_elementor_page_settings', true );
			$settings = is_string( $raw ) ? json_decode( $raw, true ) : ( is_array( $raw ) ? $raw : null );
			return array(
				'ok'       => true,
				'kit_id'   => $kit_id,
				'keys'     => is_array( $settings ) ? array_keys( $settings ) : array(),
				'settings' => $settings,
			);
		},
	) );

	// Gravar settings genéricas no Kit (Theme Style: body_typography, hX_typography, etc).
	// Body: { settings: { <chave>: <valor>, ... } }
	register_rest_route( 'allure/v1', '/set-kit-settings', array(
		'methods'             => 'POST',
		'permission_callback' => $perm,
		'callback'            => function ( WP_REST_Request $req ) {
			if ( ! class_exists( '\Elementor\Plugin' ) ) {
				return new WP_Error( 'allure_no_elementor', 'Elementor não está ativo.', array( 'status' => 500 ) );
			}
			$body = $req->get_params();
			$settings = isset( $body['settings'] ) && is_array( $body['settings'] ) ? $body['settings'] : null;
			if ( empty( $settings ) ) {
				return new WP_Error( 'allure_no_settings', 'Envie um objeto "settings".', array( 'status' => 400 ) );
			}
			$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit_for_frontend();
			if ( ! $kit ) {
				return new WP_Error( 'allure_no_kit', 'Kit ativo não encontrado.', array( 'status' => 500 ) );
			}
			allure_kit_save_merged( $kit, $settings ); // merge p/ não apagar outras settings
			return array(
				'ok'      => true,
				'kit_id'  => $kit->get_id(),
				'applied' => array_keys( $settings ),
			);
		},
	) );

	// Subir um arquivo (logo/ícone) pra Media Library a partir de base64.
	// Body: { filename, b64, mime?, set_site_logo?:bool }
	register_rest_route( 'allure/v1', '/import-media', array(
		'methods'             => 'POST',
		'permission_callback' => $perm,
		'callback'            => function ( WP_REST_Request $req ) {
			$body = $req->get_params();
			$filename = isset( $body['filename'] ) ? sanitize_file_name( $body['filename'] ) : '';
			$b64      = isset( $body['b64'] ) ? $body['b64'] : '';
			if ( ! $filename || ! $b64 ) {
				return new WP_Error( 'allure_no_file', 'Envie filename e b64.', array( 'status' => 400 ) );
			}
			$data = base64_decode( $b64, true );
			if ( false === $data ) {
				return new WP_Error( 'allure_bad_b64', 'base64 inválido.', array( 'status' => 400 ) );
			}

			// SVG: sanitização mínima (remove <script> e handlers on*). Yuza é descartável.
			$is_svg = ( substr( strtolower( $filename ), -4 ) === '.svg' );
			if ( $is_svg ) {
				$str = (string) $data;
				$str = preg_replace( '#<script[^>]*>.*?</script>#is', '', $str );
				$str = preg_replace( '#\son\w+\s*=\s*("[^"]*"|\'[^\']*\')#i', '', $str );
				$data = $str;
			}

			$upload = wp_upload_bits( $filename, null, $data );
			if ( ! empty( $upload['error'] ) ) {
				return new WP_Error( 'allure_upload_fail', $upload['error'], array( 'status' => 500 ) );
			}

			$mime = $is_svg ? 'image/svg+xml' : ( isset( $body['mime'] ) ? sanitize_text_field( $body['mime'] ) : ( wp_check_filetype( $upload['file'] )['type'] ?: '' ) );
			$attach = array(
				'post_mime_type' => $mime,
				'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
				'post_status'    => 'inherit',
			);
			$attach_id = wp_insert_attachment( $attach, $upload['file'] );
			if ( is_wp_error( $attach_id ) ) { return $attach_id; }

			require_once ABSPATH . 'wp-admin/includes/image.php';
			$meta = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
			wp_update_attachment_metadata( $attach_id, $meta );

			$did_logo = false;
			if ( ! empty( $body['set_site_logo'] ) ) {
				set_theme_mod( 'custom_logo', $attach_id );
				$did_logo = true;
			}

			return array(
				'ok'            => true,
				'id'            => $attach_id,
				'url'           => wp_get_attachment_url( $attach_id ),
				'mime'          => $mime,
				'set_site_logo' => $did_logo,
			);
		},
	) );

} );

// Habilita upload de SVG na Media Library (Yuza é ambiente de teste isolado).
add_filter( 'upload_mimes', function ( $mimes ) {
	$mimes['svg']  = 'image/svg+xml';
	$mimes['svgz'] = 'image/svg+xml';
	return $mimes;
} );
// Evita o WP rejeitar o SVG por "real mime" divergente.
add_filter( 'wp_check_filetype_and_ext', function ( $data, $file, $filename ) {
	if ( substr( strtolower( $filename ), -4 ) === '.svg' ) {
		$data['ext']  = 'svg';
		$data['type'] = 'image/svg+xml';
	}
	return $data;
}, 10, 3 );

/**
 * Salva settings no Kit MESCLANDO com as já existentes (o save do Elementor
 * SUBSTITUI tudo — sem isso, gravar typography apaga colors, e vice-versa).
 * Lê o _elementor_page_settings cru (só valores explicitamente setados), faz
 * array_merge no nível de topo (system_colors, system_typography, body_typography…)
 * e regenera o CSS global.
 */
function allure_kit_save_merged( $kit, $new_settings ) {
	$raw = get_post_meta( $kit->get_id(), '_elementor_page_settings', true );
	$existing = is_string( $raw ) ? json_decode( $raw, true ) : ( is_array( $raw ) ? $raw : array() );
	if ( ! is_array( $existing ) ) { $existing = array(); }
	$merged = array_merge( $existing, $new_settings );
	$kit->save( array( 'settings' => $merged ) );
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}

/* -------------------------------------------------------------------------
 * Fonte Aspekta (auto-hospedada): registra na lista do Elementor + imprime
 * o @font-face no front e no preview do editor. Arquivo esperado:
 * wp-content/mu-plugins/AspektaVF.woff2 (variável, eixo de peso 50–1000).
 * ---------------------------------------------------------------------- */

// 1. Adiciona o grupo "Allure" no seletor de fontes do Elementor.
add_filter( 'elementor/fonts/groups', function ( $groups ) {
	$groups['allure'] = 'Allure';
	return $groups;
} );

// 2. Registra "Aspekta" nesse grupo (fica selecionável nos controles de tipografia).
add_filter( 'elementor/fonts/additional_fonts', function ( $fonts ) {
	$fonts['Aspekta'] = 'allure';
	return $fonts;
} );

// Detecta quais arquivos da fonte estão presentes em mu-plugins/.
function allure_aspekta_info() {
	$dir = WPMU_PLUGIN_DIR;
	return array(
		'woff2_variable' => file_exists( $dir . '/AspektaVF.woff2' ),
		'otf_350'        => file_exists( $dir . '/Aspekta-350.otf' ),
		'otf_650'        => file_exists( $dir . '/Aspekta-650.otf' ),
	);
}

// Monta o CSS de @font-face: prefere o woff2 variável; senão usa os OTFs estáticos.
function allure_aspekta_faces_css() {
	$url  = WPMU_PLUGIN_URL;
	$info = allure_aspekta_info();
	$faces = array();
	if ( $info['woff2_variable'] ) {
		$faces[] = '@font-face{font-family:"Aspekta";src:url("' . esc_url( $url . '/AspektaVF.woff2' ) . '") format("woff2-variations");font-weight:50 1000;font-style:normal;font-display:swap;}';
	} else {
		// Mapeia os pesos estáticos pros valores padrão do seletor do Elementor:
		// Aspekta-350 -> 300/400 (leve/corpo), Aspekta-650 -> 600/700 (títulos).
		if ( $info['otf_350'] ) {
			$faces[] = '@font-face{font-family:"Aspekta";src:url("' . esc_url( $url . '/Aspekta-350.otf' ) . '") format("opentype");font-weight:300 400;font-style:normal;font-display:swap;}';
		}
		if ( $info['otf_650'] ) {
			$faces[] = '@font-face{font-family:"Aspekta";src:url("' . esc_url( $url . '/Aspekta-650.otf' ) . '") format("opentype");font-weight:500 700;font-style:normal;font-display:swap;}';
		}
	}
	return implode( '', $faces );
}

// Imprime o @font-face. wp_head cobre front + iframe de preview do editor.
$allure_font_face = function () {
	$css = allure_aspekta_faces_css();
	if ( $css ) {
		echo '<style id="allure-aspekta-face">' . $css . '</style>' . "\n"; // phpcs:ignore
	}
};
add_action( 'wp_head', $allure_font_face );
add_action( 'elementor/editor/wp_head', $allure_font_face );
