<?php

Class Factura implements JsonSerializable
{

    private $cabecera = array();
    private $detalle = array();

    public function __construct($serie, $correlativo, $fechaEmision, $moneda, $tipoOperacion = '01', $subTipoOperacion = '01')
    {
        $this->cabecera['tipoOperacion'] = $tipoOperacion;
        $this->cabecera['subTipoOperacion'] = $subTipoOperacion;
        $this->cabecera['serie'] = $serie;
        $this->cabecera['correlativo'] = $correlativo;
        $this->cabecera['fechaEmision'] = $fechaEmision;
        if (is_array($moneda)) {
            $this->cabecera['tipoMoneda'] = $moneda['tipo'];
            $this->cabecera['tipoCambio'] = $moneda['cambio'];
        } else {
            $this->cabecera['tipoMoneda'] = $moneda['tipo'];
        }
    }

    public function operacion($tipoOperacion, $subTipoOperacion) {
        $this->cabecera['tipoOperacion'] = $tipoOperacion;
        $this->cabecera['subTipoOperacion'] = $subTipoOperacion;

        return $this;
    }

    public function cliente($tipoIdentidad, $numeroIdentidad, $nombre, $nombreComercial, $direccion, $email = null)
    {
        $this->cabecera['adquiriente'] = array(
            'tipoIdentidad' => $tipoIdentidad,
            'numeroIdentidad' => $numeroIdentidad,
            'nombre' => $nombre,
            'nombreComercial' => $nombreComercial,
            'direccion' => array(),
            'email' => $email
        );

        if (is_array($direccion)) {
            $this->cabecera['adquiriente']['direccion']['descripcion'] = $direccion['descripcion'];
            if (isset($direccion['codigoPais'])) {
                $this->cabecera['adquiriente']['direccion']['codigoPais'] = $direccion['codigoPais'];
            }
            if (isset($direccion['ubigeo'])) {
                $this->cabecera['adquiriente']['direccion']['ubigeo'] = $direccion['ubigeo'];
            }
            if (isset($direccion['departamento'])) {
                $this->cabecera['adquiriente']['direccion']['departamento'] = $direccion['departamento'];
            }
            if (isset($direccion['provincia'])) {
                $this->cabecera['adquiriente']['direccion']['provincia'] = $direccion['provincia'];
            }
            if (isset($direccion['distrito'])) {
                $this->cabecera['adquiriente']['direccion']['distrito'] = $direccion['distrito'];
            }
        }

        return $this;
    }

    public function detalle($unidadMedida, $cantidad, $producto, $vu, $pu, $desc, $vv, $igv, $it, $adicionales = null) {
        $item = array();
        $item['orden'] = count($this->detalle) + 1;
        $item['unidadMedida'] = $unidadMedida;
        $item['cantidad'] = $cantidad;
        if (is_array($producto)) {
            if (isset($producto['codigoProducto'])) {
                $item['codigoProducto'] = $producto['codigoProducto'];
            }
            if (isset($producto['descripcion'])) {
                $item['descripcion'] = $producto['descripcion'];
            }
            if (isset($producto['multiDescripcion'])) {
                $item['multiDescripcion'] = $producto['multiDescripcion'];
            }
        }
        $item['valorUnitario'] = $vu;
        $item['precioUnitario'] = $pu;
        $item['montoDescuento'] = $desc;

        $item['$vv'] = $vv;
        $item['igv'] = array();
        if (is_array($igv)) {
            if (isset($igv['monto'])) {
                $item['igv']['monto'] = $igv['monto'];
            }
            if (isset($igv['afectacion'])) {
                $item['igv']['codigoTipoAfectacionIgv'] = $igv['afectacion'];
            }
        } else {
            $item['igv']['monto'] = $igv;
            $item['igv']['codigoTipoAfectacionIgv'] = '10';
        }
        $item['importeTotal'] = $it;
        $item['adicionales'] = $adicionales;

        $this->detalle[] = $item;

        return $this;
    }

    public function totales($oG, $oI, $oE, $oF, $igv, $importe, $montoLetras) {
        $this->cabecera['operacionGravada'] = $oG;
        $this->cabecera['operacionInafecta'] = $oI;
        $this->cabecera['operacionExonerada'] = $oE;
        $this->cabecera['operacionGratuita'] = $oF;
        $this->cabecera['igv'] = array();
        if (is_array($igv)) {
            if (isset($igv['monto'])) {
                $this->cabecera['igv']['monto'] = $igv['monto'];
            }
            if (isset($igv['montoGratuito'])) {
                $this->cabecera['igv']['montoGratuito'] = $igv['montoGratuito'];
            }
        } else {
            $this->cabecera['igv']['monto'] = $igv;
        }
        $this->cabecera['importes'] = array();
        if (is_array($importe)) {
            if (isset($importe['total'])) {
                $this->cabecera['importes']['importeTotal'] = $importe['total'];
            }
            if (isset($importe['descuentosGlobales'])) {
                $this->cabecera['importes']['descuentosGlobales'] = $importe['descuentosGlobales'];
            }
            if (isset($importe['otrosCargos'])) {
                $this->cabecera['importes']['otrosCargos'] = $importe['otrosCargos'];
            }
        } else {
            $this->cabecera['importes']['importeTotal'] = $importe;
        }
        $this->cabecera['montoLetras'] = $montoLetras;
    }

    public function detraccion($codigo, $porcentaje, $monto, $ot) {
        $detraccion = array();

        $detraccion['codigo'] = $codigo;
        $detraccion['porcentaje'] = $porcentaje;
        $detraccion['monto'] = $monto;

        if (is_array($ot)) {
            if (isset($ot['cuenta'])) {
                $detraccion['cuenta'] = $ot['cuenta'];
            }
            $transport = array();
            if (isset($ot['transporte'])) {
                $transport['valorReferencial'] = $ot['transporte']['valorReferencial'];
                $transport['valorReferencialCargaEfectiva'] = $ot['transporte']['valorReferencialCargaEfectiva'];
                $transport['valorReferencialCargaUtil'] = $ot['transporte']['valorReferencialCargaUtil'];
                $transport['ubigeoOrigen'] = $ot['transporte']['ubigeoOrigen'];
                $transport['puntoOrigen'] = $ot['transporte']['puntoOrigen'];
                $transport['ubigeoDestino'] = $ot['transporte']['ubigeoDestino'];
                $transport['puntoDestino'] = $ot['transporte']['puntoDestino'];
                $transport['tramoViaje'] = $ot['transporte']['tramoViaje'];
                $detraccion['transporte'] = $transport;
            }
            $hidro = array();
            if (isset($ot['recursosHidrobiologicos'])) {
                $hidro['matriculaEmbarcacion'] = $ot['recursosHidrobiologicos']['matriculaEmbarcacion'];
                $hidro['nombreEmbarcacion'] = $ot['recursosHidrobiologicos']['nombreEmbarcacion'];
                $hidro['descripcionEspecie'] = $ot['recursosHidrobiologicos']['descripcionEspecie'];
                $hidro['lugarDescarga'] = $ot['recursosHidrobiologicos']['lugarDescarga'];
                $hidro['cantidadEspecie'] = $ot['recursosHidrobiologicos']['cantidadEspecie'];
                $detraccion['transporte'] = $transport;
            }
        }

        $this->cabecera['detraccion'] = $detraccion;
    }

    public function jsonSerialize(){
        return ['cabecera' => $this->cabecera, 'detalle' => $this->detalle];
    }

}
