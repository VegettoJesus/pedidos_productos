<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TiendaController extends Controller
{
    public function home()
    {
        $data = new \stdClass();
        $data->contenido2 = 'tienda.main';
        return view('layouts.contenido2', (array) $data);
    }
    
    // Puedes agregar más métodos para la tienda
    public function productos()
    {
        return view('tienda.productos');
    }
    
    public function contacto()
    {
        return view('tienda.contacto');
    }
}