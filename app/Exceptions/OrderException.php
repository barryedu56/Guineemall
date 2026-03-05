<?php

namespace App\Exceptions;

use Exception;

class OrderException extends Exception
{
    /**
     * Render the exception as an HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Order Error',
                'message' => $this->getMessage()
            ], 422);
        }

        return back()->with('error', $this->getMessage());
    }
}
