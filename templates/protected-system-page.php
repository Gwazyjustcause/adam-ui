<?php
/**
 * Friendly protected system-page response.
 *
 * @package ADAM_UI
 */

defined( 'ABSPATH' ) || exit;
?>
<main id="primary" class="adam-protected-page" style="max-width:760px;margin:clamp(3rem,10vw,8rem) auto;padding:0 1.5rem;text-align:center;">
	<section class="adam-card" aria-labelledby="adam-protected-page-title" style="padding:clamp(2rem,6vw,4rem);">
		<p aria-hidden="true" style="font-size:3rem;margin:0 0 1rem;">🤫</p>
		<h1 id="adam-protected-page-title"><?php esc_html_e( 'Psst...', 'adam-ui' ); ?></h1>
		<p><?php esc_html_e( 'Esta página não está disponível de momento.', 'adam-ui' ); ?></p>
		<p><?php esc_html_e( 'O acesso a esta página é reservado ou efetuado através de uma ação específica no site.', 'adam-ui' ); ?></p>
		<p><?php esc_html_e( 'Se acredita que deveria conseguir aceder a esta página, entre em contacto connosco.', 'adam-ui' ); ?></p>
		<p style="margin-top:2rem;"><a class="button adam-button adam-button-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Voltar à Página Inicial', 'adam-ui' ); ?></a></p>
	</section>
</main>
