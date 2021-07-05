<?php

global $wpdb;

do_action("billet_param");

$table = $wpdb->prefix. 'billet';
$event_param = $wpdb->get_results($wpdb->prepare("SELECT event_name, event_date, nb_places_max, nb_places_reste
                                                FROM $table WHERE 1=%d ORDER BY event_date", 1));
?>

<div class="wrap">
    <h1>Gestion des évènements</h1>
    <table class="form-table table_event">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Date</th>
                <th>Places maximum</th>
                <th>Places disponibles</th>
            </tr>
        </thead>
        <?php foreach($event_param as $my_event) {
            $event_date = new DateTime($my_event->event_date)
            ?>
            <tr valign="top">
                <td><?php echo $my_event->event_name ?></td>
                <td><?php echo $event_date->format("d/m/Y") ?></td>
                <td><?php echo $my_event->nb_places_max ?></td>
                <td><?php echo $my_event->nb_places_reste ?></td>
            </tr>
        <?php } ?>
    </table>
</div>

<div class="wrap">
    <!-- formulaire d'ajout -->
    <form action="#" method="post">
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Nom de l'évènement : </th>
                <td><input type="text" name="event_name" size="100" /></td>
            </tr>

            <tr valign="top">
                <th scope="row">Description de l'évènement : </th>
                <td><textarea name="event_description" cols="100" rows="10"></textarea></td>
            </tr>

            <tr valign="top">
                <th scope="row">Date de l'évènement : </th>
                <td><input type="date" name="event_date" /></td>
            </tr>

            <tr valign="top">
                <th scope="row">Tarif de l'évènement : </th>
                <td><input type="text" name="event_price" size="100"/></td>
            </tr>

            <tr valign="top">
                <th scope="row">Nombre de places : </th>
                <td><input type="text" name="event_place" size="100"/></td>
            </tr>
        </table>
        <!-- affichage bouton - fonction worpress -->
        <?php submit_button(); ?>
    </form>
</div>