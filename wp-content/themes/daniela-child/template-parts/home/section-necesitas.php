<?php

/**
 * Home section — ¿Qué necesitas?
 */
if (! defined('ABSPATH')) {
    exit;
}
?>
<section class="dm-home-necesitas" aria-labelledby="dm-home-necesitas-title">
    <div class="dm-home-necesitas__inner">
        <h2 id="dm-home-necesitas-title">¿Qué necesitas?</h2>

        <div class="dm-home-necesitas__grid">
            <a class="dm-home-necesitas__card" href="<?php echo esc_url(home_url('/recursos/')); ?>">
                <h3>Recursos</h3>
                <p>PDFs y guías prácticas para avanzar hoy.</p>
            </a>

            <a class="dm-home-necesitas__card" href="<?php echo esc_url(home_url('/escuela/cursos/')); ?>">
                <h3>Cursos</h3>
                <p>Aprendizaje paso a paso a tu ritmo.</p>
            </a>

            <a class="dm-home-necesitas__card" href="<?php echo esc_url(home_url('/escuela/talleres/')); ?>">
                <h3>Talleres</h3>
                <p>Experiencias en vivo para trabajar en comunidad.</p>
            </a>

            <a class="dm-home-necesitas__card" href="<?php echo esc_url(home_url('/programas/')); ?>">
                <h3>Programas</h3>
                <p>Procesos más profundos y acompañados.</p>
            </a>
        </div>
    </div>
</section>