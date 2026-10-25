<?php

return [

    /*
    | Batch dianggap hampir kedaluwarsa jika sisa harinya kurang dari atau
    | sama dengan nilai ini.
    */
    'near_expiry_days' => (int) env('NEAR_EXPIRY_DAYS', 90),

];
