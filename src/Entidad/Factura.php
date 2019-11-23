<?php

Class Factura
{

    public $cabecera = array();

    public function __construct($serie, $correlativo, $fechaEmision, $moneda)
    {
        $cabecera['serie'] = $serie;
        $cabecera['correlativo'] = $correlativo;
        $cabecera['fechaEmision'] = $fechaEmision;
        if (is_array($moneda)) {
            $cabecera['tipoMoneda'] = $moneda['tipo'];
            $cabecera['tipoCambio'] = $moneda['cambio'];
        } else {
            $cabecera['tipoMoneda'] = $moneda['tipo'];
        }
    }

    public function cliente($tipoIdentidad, $numeroIdentidad, $nombre, $nombreComercial, $direccion, $email = null)
    {
        $cabecera['adquiriente'] = array(
            'tipoIdentidad' => $tipoIdentidad,
            'numeroIdentidad' => $numeroIdentidad,
            'nombre' => $nombre,
            'nombreComercial' => $nombreComercial,
            'direccion' => array(),
            'email' => $email
        );

        if (is_array($direccion)) {
            $cabecera['adquiriente']['direccion']['descripcion'] = $direccion['descripcion'];
            if (isset($direccion['codigoPais'])) {
                $cabecera['adquiriente']['direccion']['codigoPais'] = $direccion['codigoPais'];
            }
            if (isset($direccion['ubigeo'])) {
                $cabecera['adquiriente']['direccion']['ubigeo'] = $direccion['ubigeo'];
            }
            if (isset($direccion['departamento'])) {
                $cabecera['adquiriente']['direccion']['departamento'] = $direccion['departamento'];
            }
            if (isset($direccion['provincia'])) {
                $cabecera['adquiriente']['direccion']['provincia'] = $direccion['provincia'];
            }
            if (isset($direccion['distrito'])) {
                $cabecera['adquiriente']['direccion']['distrito'] = $direccion['distrito'];
            }
        }

        return $this;
    }

    /*public function detalle($unidadMedida, $cantidad)*/
}