<ul class="livestats">
   <li>
      <span class="title">Queue</span>
      <strong>{!! $queue !!}</strong>
   </li>
   <li>
      <span class="title">Proc</span>
      <strong>{!! $processed !!}</strong>
   </li>
   <?php if ($errored!='') { ?>
   <li>
      <span class="title">Err</span>
      <strong>{!! $errored !!}</strong>
   </li>
   <?php } ?>
</ul>