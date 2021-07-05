<?php 

global $wpdb;

$charset_collate = $wpdb->get_charset_collate();

$table_name = $wpdb->prefix. 'billet';

$query = "CREATE TABLE IF NOT EXISTS $table_name(
    event_id bigint(20) NOT NULL AUTO_INCREMENT,
    prod_id bigint(20) NOT NULL,
    event_name varchar(50) DEFAULT NULL,
    event_date DATE DEFAULT NULL,
    event_price decimal(10,2),
    nb_places_max INT(11),
    nb_places_reste INT(11),
    PRIMARY KEY (event_id)
    ) $charset_collate; ";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($query);

function link_billeterie() {
    wp_enqueue_script(
        'Mon_agendaJS',
        plugins_url('../js/Mon_billeterie.js', __FILE__),
        '',
        '',
        false
    );
}

add_action('wp_enqueue_scripts', 'link_billeterie');

function Add_billeterie_admin_menu() {
    add_menu_page(
        'Saisie des évènements',
        'Ma billeterie',
        'manage_options',
        plugin_dir_path(__FILE__) . 'Ma_billeterie_param.php'
    );
}

add_action('admin_menu', 'Add_billeterie_admin_menu');

function new_event_billeterie() {
    if(isset($_POST["event_name"]) && isset($_POST["event_description"]) 
    && isset($_POST["event_date"]) && isset($_POST["event_price"]) && isset($_POST["event_place"])) {
        $data = (!empty($_POST)) ? $_POST : array();
        $data['errors'] = array();

        $data = apply_filters('do_event_submission', $data);
    }
}

add_action('billet_param', 'new_event_billeterie');

add_filter('do_event_submission', 'event_billeterie_prepare_submission', 10, 1);

function event_billeterie_prepare_submission($data) {
    $new_data['my_event'] = array(
        'event_name' => sanitize_text_field($data["event_name"]),
        'event_description' => sanitize_text_field($data["event_description"]),
        'event_date' => sanitize_text_field($data["event_date"]),
        'event_price' => sanitize_text_field($data["event_price"]),
        'event_place' => sanitize_text_field($data["event_place"])
    );

    $data = $new_data;
    return $data;
}

add_filter('do_event_submission', 'valid_event_billet', 20, 1);

function valid_event_billet($data) {
    if(empty($data['errors']) && array_key_exists('my_event', $data)) {
        $new_data['product'] = array(
            'name' => $data["my_event"]["event_name"],
            'price' => $data["my_event"]["event_price"],
            'description' => $data["my_event"]["event_description"],
            'qte' => $data["my_event"]["event_place"],
            'date' => $data["my_event"]["event_date"],
            'product_meta' => array());
            
        $new_data['product']['product_meta']['_stock'] = $data["my_event"]["event_place"];
        $new_data['product']['product_meta']['_manage_stock'] = "yes";

        $data = $new_data;
        return $data;
    }
}

function creation_produit($data) {
    $product = new WC_Product_simple;

        $product->set_name($data['product']['name']);
        $product->set_description($data['product']['description']);
        $product->set_regular_price($data['product']['price']);
        $product->set_stock_quantity($data['product']['qte']);

    if($product && is_array($data['product']['product_meta'])) {
        foreach($data['product']['product_meta'] as $meta_key => $meta_value) {
            $product->update_meta_data($meta_key, $meta_value);
        }
    }
    $product->save();

    $product_id = $product->get_id();

    global $wpdb;
        $table = $wpdb->prefix. 'billet';
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table
            (prod_id, event_name, event_price, event_date, nb_places_max, nb_places_reste) VALUES (%d,%s,%d,%s,%d,%d)",
            $product_id,
            $data["product"]["name"],
            $data["product"]["price"],
            $data["product"]["date"],
            $data["product"]["qte"],
            $data["product"]["qte"],
        ));
}

add_filter('do_event_submission', 'creation_produit', 20, 1);

function ma_billeterie_shortCode() {
    $lien = get_permalink();

    if(isset($_GET['mois']) && isset($_GET['annee'])) {
        $mois = $_GET['mois'];
        $annee = $_GET['annee'];
    } else {
        $mois = date('n');
        $annee = date('Y');
    }

    //liste des évènements existants
    
    $l_day = date("t", mktime(0,0,0, $mois, 1, $annee));

    // Numéro du jour (lundi =1, mardi=2, etc) du premier jour du mois
    $x = date("N", mktime(0,0,0, $mois, 1, $annee));

    // Numéro du jour (lundi =1, mardi=2, etc) du premier jour du mois
    $y = date("N", mktime(0,0,0, $mois, $l_day, $annee));

    $mois_fr = Array("", "Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre");

    //Construction du shortcode
    //construction de la liste des mois

    $monagenda ='
    <form name="dt" id="dt" method="get" action="">
    <select name="mois" id="mois" onChange="change()">';
        for($i =1;$i<13;$i++) {
            $monagenda.= '<option value="'.$i.'"';
            if($i == $mois) {
                $monagenda.= ' selected >' .$mois_fr[$i].'</option>';
            } else {
                $monagenda.= '>'. $mois_fr[$i].'</option>';
            }
        }

    //Liste des annees
    $monagenda.='</select>';
    $monagenda.='<select name="annee" id="annee" onChange="change()">';
    for($i =2021;$i<2035;$i++) {
        $monagenda.= '<option value="'.$i.'"';
        if($i == $annee) {
            $monagenda.= ' selected >'.$i.'</option>';
        } else {
            $monagenda.= '>'.$i.'</option>';
        }
    }
    $monagenda.='</select>
    </form>';

    $monagenda .= '
    <table>
    <tr><th>Lun</th>
    <th>Mar</th>
    <th>Mer</th>
    <th>Jeu</th>
    <th>Ven</th>
    <th>Sam</th>
    <th>Dim</th></tr>
    <tr>';

    $case = 0;

    if($x > 1) {
        for($i = 1; $i < $x; $i++) {
            $monagenda.= '<td>&nbsp;</td>';
            $case++;
        }
    }
    for($i = 1; $i < ($l_day +1); $i++) {
        $da = $annee . "-" .$mois. "-" .$i;
        $monagenda.= "<td><a class='a-tab' href='".
            $lien."?aff=event&d=$da'> $i</a></td>";
        $case++;
        if($case % 7 == 0) {
            $monagenda.= "</tr><tr>";
        }
    }

    if($y != 7) {
        for($i = $y; $i < 7; $i++) {
            $monagenda.= '<td>&nbsp;</td>';
        }
    }
    $monagenda.= '</tr></table></center>';

    //Si on cliqué sur une des cases de l'agenda, on regarde si cette
    //date correspond à un évènement stocké dans notre tableau
    //Si oui, on affiche le nom de ce / ces évènements

    if (isset($_GET['aff'])) {
        /** */
        global $wpdb;
        $table = $wpdb->prefix . 'billet';
        $list_event = $wpdb->get_results($wpdb->prepare(
            "SELECT prod_id,
                    event_name,
                    event_date,
                    event_price
            FROM $table  WHERE 1=%d ORDER BY event_date",
            1
        ));

        $monagenda .= '<h3>
                            Évènements du : ' . french_Date($_GET["d"]) .
            '</h3>';
        $monagenda .= '<ul>';

        foreach ($list_event as $my_event) {
            $monagenda.= '<form method="post">';
            $the_date = new DateTime($my_event->event_date);
            if ($the_date->format("Y-n-j") == $_GET["d"]) {
                $monagenda .= '<input type="text" value="' . $my_event->event_name . '"/>';
            }

            $monagenda.= '<input type="text" value="' . $my_event->event_price . ' €" />';
            $monagenda.= '<input type="hidden" name="prod_id" value="'.$my_event->prod_id. '"/>';

            if($my_event->nb_places_reste <= $my_event->nb_places_max) {
            $monagenda.= '<button class="button" type="submit" name="submit-billet"
            value="submit-billet">
            Ajouter au panier
           </button>';
            }

            /** */
        }
        $monagenda .= '</form>';
    }

    return $monagenda;
}

function french_Date($thedate)
{
    if ($thedate != NULL) {
        $pos = strpos($thedate, '-');
        $annee = substr($thedate, 0, $pos);

        $thedate = substr($thedate, $pos + 1, strlen($thedate));
        $pos = strpos($thedate, '-');
        $mois = substr($thedate, 0, $pos);
        if (strlen($mois) != 2) {
            $mois = '0' . $mois;
        }
        $jour = substr($thedate, $pos + 1, strlen($thedate));
        if (strlen($jour) != 2) {
            $jour = '0' . $jour;
        }
        return $jour . "/" . $mois . "/" . $annee;
    }
}

function billet_submission()
{
 if (isset($_POST['submit-billet']) && $_POST['submit-billet'] == "submit-billet") {
 
 $data = (!empty($_POST)) ? $_POST : array();
 $data['errors'] = array();
 
 $data = apply_filters('do_billet_submission', $data);
 }
}


add_action('template_redirect', 'billet_submission');

function add_billet($data) {
    $cart=WC()->cart;

    if(empty($cart->get_cart())){
        $cart = new WC_Cart;
    }

    $cart->add_to_cart($data["prod_id"], 1);
}

add_filter('do_billet_submission', 'add_billet');

add_shortcode('mon_agenda', 'ma_billeterie_shortCode');


function calcul_cart() {
global $woocommerce;

foreach($woocommerce->cart->cart_contents as $cart_item_key => $cart_item) {
    global $wpdb;
        $table = $wpdb->prefix . 'billet';
        $list_event = $wpdb->get_row($wpdb->prepare(
            "SELECT prod_id, nb_places_reste
            FROM $table  WHERE prod_id = %d", $cart_item['product_id']
        ));

        if($list_event->nb_places_reste < $cart_item['quantity']) {
            WC()->cart->set_quantity($cart_item_key, $event->nb_places_reste, true);
        }
    }
}

add_action('woocommerce_before_cart', 'calcul_cart');

function update_billet($order) {
    $order = new WC_Order($order);

    foreach($order->get_items() as $item) {
        global $wpdb;
        $table = $wpdb->prefix . 'billet';
        $list_event = $wpdb->get_results($wpdb->prepare(
            "SELECT prod_id, nb_places_reste
            FROM $table  WHERE prod_id = %d", $item['product_id']
        ));

        
    }

        if($order) {
            $reste = $list_event->nb_places_reste - $item['quantity'];
            global $wpdb;
            $table = $wpdb->prefix . 'billet';

            $update = $wpdb->prepare("UPDATE $table 
            SET nb_places_reste = %d
            WHERE prod_id = %d", 
            $item['product_id'],
            $reste
        );
    }
}

add_action('woocommerce_order_status_processing', 'update_billet', 10, 1);

function mondebug($texte){

    $fichier = ("C:/debug/debug.txt");
file_put_contents($fichier,date('d/m/Y')." : ".$texte."\r",FILE_APPEND);

}
