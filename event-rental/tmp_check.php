<?php
foreach(\App\Models\User::all() as $u) {
    $ws = $u->workspaces->first();
    echo $u->email . ' (' . $u->id . ') - workspace: ' . ($ws ? $ws->id : 'none') . "\n";
}
echo 'Events workspace_id: ' . \App\Models\Event::first()->workspace_id . "\n";
