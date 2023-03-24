<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetTransactionOperations
{
    public function handle(Request $request, Closure $next)
    {
        // Determine the type of units through the process ...
        $operation_type = $request->operation_type;
        $from_units_name = null;
        $to_units_name = null;

        if ($operation_type == 'CENTRAL_OBSTETRICS') {
            $from_units_name = 'GENERATED_UNIT';
        } elseif ($operation_type == 'INDEPENDENCE') {
            $from_units_name = 'GENERATED_UNIT';
            $to_units_name   = 'THE_UNIT_IS_INDEPENDENT';
        } elseif ($operation_type == 'withdrawal') {
            $units_name = 'expense';
        }

        $request->attributes->set('FROM_UNIT_NAME', $from_units_name);
        $request->attributes->set('TO_UNIT_NAME', $to_units_name);
        return $next($request);
    }
}
