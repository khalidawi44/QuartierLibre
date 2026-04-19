<?php
/**
 * Variantes du Bureau des plaintes adaptées au contexte de l'article.
 *
 * Chaque article peut spécifier une variante via le frontmatter Markdown :
 *     plainte_variant: "immigration"  # ou "police", "logement", "default"
 *
 * Sinon, détection automatique basée sur la catégorie / les tags.
 * Sinon fallback sur la variante 'default'.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Retourne la liste complète des variantes configurées.
 * Chaque variante a :
 *  - label     : titre affiché en haut de la modal
 *  - kicker    : petit badge au-dessus du titre
 *  - subtitle  : texte de sous-titre explicatif
 *  - type_options : array [valeur => label] pour le select "Type de problème"
 *  - extra_fields : champs supplémentaires spécifiques (array de configs)
 *  - emergency_notice : message d'urgence affiché en haut (optionnel)
 */
function ql_plainte_variants() {
    return array(

        'default' => array(
            'label'    => 'Bureau des plaintes',
            'kicker'   => 'Parole libre',
            'subtitle' => 'Problème de logement, démarche bloquée, violence, service public défaillant — racontez. La rédaction enquête, recoupe, publie.',
            'type_options' => array(
                'Logement'           => 'Logement / habitat',
                'Administratif'      => 'Démarche administrative',
                'Sécurité & police'  => 'Sécurité & police',
                'Services publics'   => 'Services publics / école',
                'Transports'         => 'Transports',
                'Emploi'             => 'Emploi',
                'Autre'              => 'Autre',
            ),
            'extra_fields' => array(),
        ),

        'immigration' => array(
            'label'    => 'Aide — Immigration & asile',
            'kicker'   => 'Urgence',
            'subtitle' => 'Loi asile-immigration 2026 : vous ou un·e proche êtes concerné·e ? Demandez de l\'aide, signalez une urgence, témoignez. <strong>Anonymat total garanti.</strong>',
            'type_options' => array(
                'Demander aide juridique'  => 'Demander de l\'aide juridique',
                'Signaler urgence'          => 'Signaler un cas d\'urgence (arrestation, rétention)',
                'Besoin hébergement'        => 'Besoin d\'hébergement',
                'Convocation police'        => 'Convocation police ou préfecture',
                'Risque expulsion'          => 'Risque d\'expulsion (OQTF, CRA)',
                'Délit de solidarité'       => 'Poursuites pour délit de solidarité',
                'Témoigner'                 => 'Témoigner d\'une situation',
                'Autre'                     => 'Autre',
            ),
            'emergency_notice' => 'Urgence immédiate ? <a href="tel:0148873132" class="ql-emergency-link">Cimade Paris : 01 48 87 31 32</a> · <a href="tel:0683619191" class="ql-emergency-link">Permanence RESF : 06 83 61 91 91</a>',
            'extra_fields' => array(),
        ),

        'police' => array(
            'label'    => 'Signaler une violence policière',
            'kicker'   => 'Témoignage',
            'subtitle' => 'Contrôle au faciès, agression, interpellation abusive, garde à vue. <strong>Témoignez</strong> — on relaie, on recoupe, on rend public. Protection de votre identité garantie.',
            'type_options' => array(
                'Contrôle au faciès'      => 'Contrôle au faciès',
                'Violence physique'       => 'Violence physique (coups, plaquage)',
                'Insulte raciste'         => 'Insulte raciste, sexiste, LGBTIphobe',
                'Interpellation abusive'  => 'Interpellation / menottage abusif',
                'Garde à vue abusive'     => 'Garde à vue abusive',
                'Fouille invasive'        => 'Fouille au corps invasive',
                'Témoin'                  => 'Témoin (pas victime directe)',
                'Autre'                   => 'Autre',
            ),
            'emergency_notice' => 'Urgence : numéro d\'avocat commis d\'office Barreau de Nantes — <a href="tel:0240893000" class="ql-emergency-link">02 40 89 30 00</a>',
            'extra_fields' => array(
                array(
                    'name'        => 'ql_when',
                    'label'       => 'Quand les faits se sont-ils produits ?',
                    'placeholder' => 'Ex : samedi 18 avril, vers 21h',
                    'type'        => 'text',
                ),
                array(
                    'name'        => 'ql_where',
                    'label'       => 'Où précisément ?',
                    'placeholder' => 'Ex : Mail du Front populaire, Bellevue',
                    'type'        => 'text',
                ),
            ),
        ),

        'logement' => array(
            'label'    => 'Signaler un problème de logement',
            'kicker'   => 'Habitat',
            'subtitle' => 'Punaises, moisissures, bailleur qui ignore, rénovation forcée, loyer abusif. On documente, on fait pression. <strong>On publie si vous nous y autorisez.</strong>',
            'type_options' => array(
                'Punaises'              => 'Punaises de lit',
                'Moisissures'           => 'Moisissures / humidité',
                'Logement insalubre'    => 'Logement insalubre (structure, plomberie)',
                'Expulsion'             => 'Menace d\'expulsion',
                'Rénovation forcée'     => 'Rénovation / relogement forcé',
                'Loyer abusif'          => 'Loyer abusif / charges contestables',
                'Bailleur absent'       => 'Bailleur injoignable',
                'Nuisibles'             => 'Autres nuisibles (cafards, rats)',
                'Autre'                 => 'Autre',
            ),
            'extra_fields' => array(
                array(
                    'name'        => 'ql_bailleur',
                    'label'       => 'Votre bailleur',
                    'type'        => 'select',
                    'options'     => array(
                        ''                           => '— Choisir —',
                        'Nantes Métropole Habitat'   => 'Nantes Métropole Habitat (NMH)',
                        'Atlantique Habitations'     => 'Atlantique Habitations',
                        'Harmonie Habitat'           => 'Harmonie Habitat',
                        'CDC Habitat'                => 'CDC Habitat',
                        'Bailleur privé'             => 'Bailleur privé / marchand de sommeil',
                        'Autre'                      => 'Autre',
                    ),
                ),
            ),
        ),

    );
}

/**
 * Retourne la clé de variante pour le contexte courant.
 * Priorité :
 *   1. meta _ql_plainte_variant de l'article courant (si single post)
 *   2. Détection basée sur les catégories (immigration, police, logement)
 *   3. 'default'
 */
function ql_plainte_current_variant_key() {
    if ( is_singular( 'post' ) ) {
        $meta = get_post_meta( get_the_ID(), '_ql_plainte_variant', true );
        if ( $meta && array_key_exists( $meta, ql_plainte_variants() ) ) {
            return $meta;
        }
        // Détection par catégorie
        $cats = wp_get_post_categories( get_the_ID(), array( 'fields' => 'slugs' ) );
        if ( in_array( 'politique', $cats, true ) || in_array( 'justice', $cats, true ) ) {
            // Par défaut pour politique on ne suppose pas immigration (trop large)
            // Il faut que l'article précise `plainte_variant: immigration`
        }
        if ( in_array( 'repression', $cats, true ) || in_array( 'breil', $cats, true ) ) {
            return 'police';
        }
        if ( in_array( 'clos-toreau', $cats, true ) || in_array( 'malakoff', $cats, true )
             || in_array( 'bottiere-pin-sec', $cats, true ) || in_array( 'bout-des-landes', $cats, true )
             || in_array( 'port-boyer', $cats, true ) ) {
            return 'logement';
        }
    }
    return 'default';
}

/**
 * Retourne la config complète de la variante courante.
 */
function ql_plainte_current_variant() {
    $variants = ql_plainte_variants();
    $key = ql_plainte_current_variant_key();
    return isset( $variants[ $key ] ) ? $variants[ $key ] : $variants['default'];
}
