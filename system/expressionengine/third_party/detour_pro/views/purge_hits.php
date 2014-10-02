<?php   if ( ! defined('BASEPATH')) exit('No direct script access allowed');
    $this->EE =& get_instance();
?>

<?php foreach ($cp_messages as $cp_message_type => $cp_message) : ?>
    <p class="notice <?=$cp_message_type?>"><?=$cp_message?></p>
<?php endforeach; ?>

<?php   
    echo '<h3>You currently have ' . number_format($total_detour_hits) . ' rows in your Detour Hits table.</h3>';

    echo '<p>Detour Pro tracks hits on a granular level by recording the Detour and the date of execution. This can create many rows in the database. You may purge the hit data by clicking the button below. You may also disable the hit counter under the extension settings. As of Detour Pro 1.5, the hit counter is turned off by default.</p>

        <p class="notice">All Detour Pro Hit Counter data will be deleted!</p>';

    echo form_open($action_url);

    echo form_submit(array('name' => 'submit', 'value' => $this->EE->lang->line('btn_purge_detours'), 'class' => 'submit'));
    echo form_close();
?>