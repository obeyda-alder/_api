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
        $units_name = null;

        if ($operation_type == 'CENTRAL_OBSTETRICS') {
            $units_name = 'GENERATED_UNIT';
        } elseif ($operation_type == 'INDEPENDENCE') {
            $units_name = [
                'from' => 'GENERATED_UNIT',
                'to'   => 'THE_UNIT_IS_INDEPENDENT',
            ];
        } elseif ($operation_type == 'withdrawal') {
            $units_name = 'expense';
        }

        $request->attributes->set('UNIT_NAME', $units_name);
        return $next($request);
    }
}
