<?php
/**
 * Atelier de rédaction — Quartier Libre
 *
 * Flux 100 % gratuit, branché sur le robot de veille :
 *   1. Le robot trouve les sujets.
 *   2. Khalid colle ici la matière récupérée sur les sites (texte, dates, chiffres,
 *      citations, liens).
 *   3. Bouton « Copier le brief pour l'assistant » → il colle ce brief dans la
 *      conversation Claude → l'assistant rédige l'article + fiche sources + visuel
 *      et pousse sur GitHub.
 *   4. Sync QL → relecture → publication.
 *
 * Aucune IA payante : WordPress ne fait qu'organiser la matière et fabriquer le brief.
 * Les dossiers (matière + statut) sont stockés dans l'option `ql_atelier_dossiers`,
 * indépendamment du robot (ils survivent à l'élagage de la veille).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ── Statuts ────────────────────────────────────────────────────
function ql_atelier_statuses() {
    return array(
        'nouveau' => array( 'Nouveau',              '#6b7280', '#f3f4f6' ),
        'matiere' => array( 'Matière prête',        '#1d4ed8', '#dbeafe' ),
        'redige'  => array( 'Rédigé (brouillon)',   '#b45309', '#fef3c7' ),
        'publie'  => array( 'Publié',               '#15803d', '#dcfce7' ),
    );
}

function ql_atelier_badge( $status ) {
    $map = ql_atelier_statuses();
    if ( ! isset( $map[ $status ] ) ) { $status = 'nouveau'; }
    list( $label, $fg, $bg ) = $map[ $status ];
    return '<span style="display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;'
         . 'font-weight:600;color:' . esc_attr( $fg ) . ';background:' . esc_attr( $bg ) . ';">'
         . esc_html( $label ) . '</span>';
}

// ── Menu (sous « Quartier Libre », avant le Robot de veille) ────
add_action( 'admin_menu', function () {
    add_submenu_page(
        'ql-dashboard',
        'Atelier de rédaction',
        '✍️ Atelier de rédaction',
        'edit_posts',
        'ql-atelier',
        'ql_atelier_render'
    );
}, 11 );

// ── Enregistrement d'un dossier ────────────────────────────────
function ql_atelier_save_from_post() {
    if ( ! isset( $_POST['ql_atelier_save'] ) ) { return; }
    if ( ! current_user_can( 'edit_posts' ) ) { wp_die( 'Non.' ); }
    check_admin_referer( 'ql_atelier_nonce' );

    $key = sanitize_text_field( wp_unslash( $_POST['ql_atelier_key'] ?? '' ) );
    if ( $key === '' ) { return; }

    $statuses = ql_atelier_statuses();
    $status   = sanitize_key( wp_unslash( $_POST['ql_atelier_status'] ?? 'nouveau' ) );
    if ( ! isset( $statuses[ $status ] ) ) { $status = 'nouveau'; }

    $material = sanitize_textarea_field( wp_unslash( $_POST['ql_atelier_material'] ?? '' ) );

    $dossiers = get_option( 'ql_atelier_dossiers', array() );
    if ( ! is_array( $dossiers ) ) { $dossiers = array(); }

    // Dossier vide + statut « nouveau » → on ne garde rien (évite le bruit).
    if ( $material === '' && $status === 'nouveau' ) {
        unset( $dossiers[ $key ] );
    } else {
        $dossiers[ $key ] = array(
            'title'    => sanitize_text_field( wp_unslash( $_POST['ql_atelier_title'] ?? '' ) ),
            'link'     => esc_url_raw( wp_unslash( $_POST['ql_atelier_link'] ?? '' ) ),
            'source'   => sanitize_text_field( wp_unslash( $_POST['ql_atelier_source'] ?? '' ) ),
            'date'     => (int) ( $_POST['ql_atelier_date'] ?? 0 ),
            'material' => $material,
            'status'   => $status,
            'updated'  => time(),
        );
    }

    update_option( 'ql_atelier_dossiers', $dossiers, false );

    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-success is-dismissible"><p>Dossier enregistré.</p></div>';
    } );
}

// ── Rendu d'une carte sujet ────────────────────────────────────
function ql_atelier_card( $key, $title, $link, $source, $date, $material, $status ) {
    $tid = 'mat_' . md5( $key );
    ?>
    <div style="border:1px solid #e2e2e2;border-left:4px solid #c0392b;border-radius:8px;padding:16px 18px;background:#fff;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
            <div style="flex:1;min-width:240px;">
                <h3 style="margin:0 0 4px;font-size:15px;line-height:1.35;"><?php echo esc_html( $title ); ?></h3>
                <p style="margin:0;font-size:12px;color:#666;">
                    <?php if ( $source ) : ?><strong><?php echo esc_html( $source ); ?></strong> · <?php endif; ?>
                    <?php if ( $date ) : ?><?php echo esc_html( date_i18n( 'd/m/Y', (int) $date ) ); ?> · <?php endif; ?>
                    <?php if ( $link ) : ?><a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener">ouvrir la source ↗</a><?php endif; ?>
                </p>
            </div>
            <div><?php echo ql_atelier_badge( $status ); ?></div>
        </div>

        <form method="post" style="margin:12px 0 0;">
            <?php wp_nonce_field( 'ql_atelier_nonce' ); ?>
            <input type="hidden" name="ql_atelier_key"    value="<?php echo esc_attr( $key ); ?>">
            <input type="hidden" name="ql_atelier_title"  value="<?php echo esc_attr( $title ); ?>">
            <input type="hidden" name="ql_atelier_link"   value="<?php echo esc_attr( $link ); ?>">
            <input type="hidden" name="ql_atelier_source" value="<?php echo esc_attr( $source ); ?>">
            <input type="hidden" name="ql_atelier_date"   value="<?php echo esc_attr( (int) $date ); ?>">

            <textarea id="<?php echo esc_attr( $tid ); ?>" name="ql_atelier_material" rows="5"
                style="width:100%;font-family:inherit;font-size:13px;"
                placeholder="Colle ici tout ce que tu récupères sur les sites : texte, dates, chiffres, citations exactes, noms, liens précis…"><?php echo esc_textarea( $material ); ?></textarea>

            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:10px;">
                <label style="font-size:13px;">Statut :
                    <select name="ql_atelier_status">
                        <?php foreach ( ql_atelier_statuses() as $skey => $s ) : ?>
                            <option value="<?php echo esc_attr( $skey ); ?>" <?php selected( $status, $skey ); ?>><?php echo esc_html( $s[0] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" name="ql_atelier_save" class="button">💾 Enregistrer la matière</button>
                <button type="button" class="button button-primary"
                    data-title="<?php echo esc_attr( $title ); ?>"
                    data-link="<?php echo esc_attr( $link ); ?>"
                    data-source="<?php echo esc_attr( $source ); ?>"
                    data-textarea="<?php echo esc_attr( $tid ); ?>"
                    onclick="qlAtelierCopyBrief(this)">📋 Copier le brief pour l'assistant</button>
            </div>
        </form>
    </div>
    <?php
}

// ── Page ───────────────────────────────────────────────────────
function ql_atelier_render() {
    if ( ! current_user_can( 'edit_posts' ) ) { wp_die( 'Non.' ); }

    ql_atelier_save_from_post();

    $dossiers = get_option( 'ql_atelier_dossiers', array() );
    if ( ! is_array( $dossiers ) ) { $dossiers = array(); }

    $pending = function_exists( 'ql_veille_pending' ) ? ql_veille_pending( 40 ) : array();

    // « En cours » : dossiers avec matière ou statut avancé (les plus récents d'abord).
    $encours = $dossiers;
    uasort( $encours, function ( $a, $b ) { return ( $b['updated'] ?? 0 ) <=> ( $a['updated'] ?? 0 ); } );

    // « Nouveaux sujets » : suggestions du robot sans dossier encore ouvert.
    $nouveaux = array();
    foreach ( $pending as $it ) {
        if ( ! isset( $dossiers[ $it['key'] ] ) ) { $nouveaux[] = $it; }
    }
    ?>
    <div class="wrap">
        <h1>✍️ Atelier de rédaction</h1>

        <div style="background:#fff8f6;border:1px solid #f0c8bf;border-radius:8px;padding:14px 18px;max-width:880px;">
            <strong>Comment ça marche (100 % gratuit)</strong>
            <ol style="margin:.5em 0 0;padding-left:1.3em;line-height:1.6;">
                <li>Le <strong>robot</strong> te propose des sujets ci-dessous.</li>
                <li>Tu ouvres la source, et tu <strong>colles la matière</strong> (texte, dates, chiffres, citations, liens) dans le sujet, puis <em>Enregistrer</em>.</li>
                <li>Tu cliques <strong>« Copier le brief pour l'assistant »</strong> et tu le colles dans la conversation Claude. L'assistant rédige l'<strong>article + fiche sources + visuel</strong> (règle : ne rien inventer) et pousse sur GitHub.</li>
                <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=ql-sync' ) ); ?>">Sync QL</a> → tu relis → tu publies.</li>
            </ol>
        </div>

        <?php if ( ! empty( $encours ) ) : ?>
            <h2 style="margin-top:26px;">📂 Dossiers en cours</h2>
            <div style="display:flex;flex-direction:column;gap:14px;max-width:880px;">
                <?php foreach ( $encours as $key => $d ) :
                    ql_atelier_card(
                        $key,
                        $d['title'] ?? '',
                        $d['link'] ?? '',
                        $d['source'] ?? '',
                        $d['date'] ?? 0,
                        $d['material'] ?? '',
                        $d['status'] ?? 'nouveau'
                    );
                endforeach; ?>
            </div>
        <?php endif; ?>

        <h2 style="margin-top:26px;">🤖 Nouveaux sujets du robot</h2>
        <?php if ( empty( $nouveaux ) ) : ?>
            <p style="color:#666;max-width:880px;">
                Aucun nouveau sujet pour l'instant.
                Va dans <a href="<?php echo esc_url( admin_url( 'admin.php?page=ql-veille' ) ); ?>">Robot de veille</a>
                et clique « Lancer la veille maintenant », ou attends le passage automatique (2×/jour).
            </p>
        <?php else : ?>
            <div style="display:flex;flex-direction:column;gap:14px;max-width:880px;">
                <?php foreach ( $nouveaux as $it ) :
                    ql_atelier_card(
                        $it['key'],
                        $it['title'] ?? '',
                        $it['link'] ?? '',
                        $it['source'] ?? '',
                        $it['date'] ?? 0,
                        '',
                        'nouveau'
                    );
                endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function qlAtelierCopyBrief(btn){
        var ta = document.getElementById(btn.dataset.textarea);
        var material = ta ? ta.value.trim() : '';
        var title  = btn.dataset.title  || '(sujet sans titre)';
        var link   = btn.dataset.link   || '—';
        var source = btn.dataset.source || '—';

        var brief =
"=== BRIEF QUARTIER LIBRE — article à rédiger ===\n" +
"SUJET : " + title + "\n" +
"SOURCE REPÉRÉE PAR LE ROBOT : " + source + " — " + link + "\n\n" +
"--- MATIÈRE QUE J'AI RÉCUPÉRÉE (textes, dates, chiffres, citations exactes, liens) ---\n" +
(material || "(rien collé pour l'instant — à compléter)") + "\n\n" +
"--- CE QUE JE VEUX EN RETOUR ---\n" +
"1) Article : content/articles/AAAA-MM-JJ-slug.md avec frontmatter YAML complet (title, slug, category[], primary_category, tags, excerpt, featured_image, status: draft, date, author parmi le roster). Ton Quartier Libre : militant, rouge/noir, façon Contre-Attaque, avec une analyse selon la ligne éditoriale.\n" +
"2) Fiche sources : content/sources/<MÊME-basename>.md au format ✓ / ⚠ / ✗ / 👤 avec des URLs PRÉCISES (jamais la page d'accueil d'une orga).\n" +
"3) Un visuel dans content/media/ + le champ featured_image qui pointe dessus.\n\n" +
"RÈGLE ABSOLUE : NE RIEN INVENTER. Toute affirmation sans source vérifiable → marquée 👤 ou retirée.\n" +
"Pousse sur GitHub en status draft quand c'est prêt.\n";

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(brief).then(function(){ qlAtelierCopied(btn); }, function(){ qlAtelierFallback(brief, btn); });
        } else {
            qlAtelierFallback(brief, btn);
        }
    }
    function qlAtelierFallback(text, btn){
        var t = document.createElement('textarea');
        t.value = text; t.style.position = 'fixed'; t.style.opacity = '0';
        document.body.appendChild(t); t.focus(); t.select();
        try { document.execCommand('copy'); } catch(e) {}
        document.body.removeChild(t); qlAtelierCopied(btn);
    }
    function qlAtelierCopied(btn){
        var old = btn.textContent;
        btn.textContent = '✓ Brief copié !';
        setTimeout(function(){ btn.textContent = old; }, 1800);
    }
    </script>
    <?php
}
