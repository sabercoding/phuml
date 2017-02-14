<?php

class plProcessonProcessor extends plProcessor {
    private $properties;

    private $output;

    private $structure;

    public $options;

    public function __construct() {
        $this->options = new plGraphvizProcessorOptions();

        $this->structure = null;
        $this->output = null;
    }

    public function getInputTypes() {
        return array(
            'application/phuml-structure'
        );
    }

    public function getOutputType() {
        return 'text/dot';
    }

    public function process($input, $type) {
        $this->structure = $input;

        $str = [];
        foreach ($this->structure as $object) {
            if ($object instanceof plPhpClass) {
                $str[] = $this->getClassDefinition($object);
            } else if ($object instanceof plPhpInterface) {
                $str[] = $this->getInterfaceDefinition($object);
            }
        }
        $this->output = '{"diagram":{"image":{"height":480,"pngdata":"iVBORw0KGgoAAAANSUhEUgAAATYAAAFCCAYAAAB7D","width":602,"y":21,"x":32},"elements":{"page":{"showGrid":true,"gridSize":15,"orientation":"portrait","height":1500,"backgroundColor":"transparent","width":1250,"padding":20},"elements":{' . implode(',', $str) . '}}},"meta":{"id":"' . sha1(mt_rand()) . '","member":"saber","exportTime":"2017-02-13 18:00:39","diagramInfo":{"category":"uml","title":"uml图","created":"' . date('Y-m-d H:i:s', time()) . '","attributes":null,"creator":"saber","modified":"2017-02-13 18:00:30"},"type":"ProcessOn Schema File","version":"1.0"}}';

        return $this->output;
    }

    private function getClassDefinition($o) {
        static $i = 0;
        $attr_names = [];
        foreach ($o->attributes as $v2) {
            $attr_names[] = (($v2->modifier == 'private') ? '-' : '+') . $v2->name . '';
        }
        $attr_name = implode('\n', $attr_names);
        $func_names = [];
        foreach ($o->functions as $v) {
            $params = [];
            foreach ($v->params as $v1) {
                $params[] = $v1->name;
            }
            $params = implode(', ', $params);
            $func_names[] = (($v->modifier == 'private') ? '-' : '+') . $v->name . '(' . $params . ')';
        }
        $func_name = implode('\n', $func_names);
        $id = rand(100, 999) . time();
        $def = '"' . $id . '":{"textBlock":[{"position":{"w":"w-20","y":"0","h":30,"x":"10"},"text":"' . $o->name . '"},{"position":{"w":"w-20","y":30,"h":' . (count($attr_names) * 15) . ',"x":"10"},"text":"' . $attr_name . '","fontStyle":{"bold":false,"textAlign":"left"}},{"position":{"w":"w-40","y":90,"h":' . (count($func_names) * 15) . ',"x":"10"},"text":"' . $func_name . '","fontStyle":{"bold":false,"textAlign":"left"}}],"lineStyle":{},"link":"","children":[],"parent":"","attribute":{"linkable":true,"visible":true,"container":false,"rotatable":true,"markerOffset":5,"collapsable":false,"collapsed":false},"fontStyle":{"bold":true},"resizeDir":["tl","tr","br","bl"],"dataAttributes":[],"shapeStyle":{"alpha":1},"id":"' . $id . '","anchors":[{"y":"0","x":"w/2"},{"y":"h","x":"w/2"},{"y":"h/2","x":"0"},{"y":"h/2","x":"w"}],"category":"uml_class","title":"类","name":"cls","fillStyle":{},"path":[{"actions":[{"action":"move","y":"4","x":"0"},{"action":"quadraticCurve","y1":"0","y":"0","x1":"0","x":"4"},{"action":"line","y":"0","x":"w-4"},{"action":"quadraticCurve","y1":"0","y":"4","x1":"w","x":"w"},{"action":"line","y":"h-4","x":"w"},{"action":"quadraticCurve","y1":"h","y":"h","x1":"w","x":"w-4"},{"action":"line","y":"h","x":"4"},{"action":"quadraticCurve","y1":"h","y":"h-4","x1":"0","x":"0"},{"action":"close"}]},{"fillStyle":{"type":"none"},"actions":[{"action":"move","y":30,"x":"0"},{"action":"line","y":30,"x":"w"},{"action":"move","y":90,"x":"0"},{"action":"line","y":90,"x":"w"}]},{"lineStyle":{"lineWidth":0},"fillStyle":{"type":"none"},"actions":[{"action":"move","y":"0","x":"0"},{"action":"line","y":"0","x":"w"},{"action":"line","y":"h","x":"w"},{"action":"line","y":"h","x":"0"},{"action":"close"}]}],"locked":false,"group":"","props":{"w":230,"heights":[30,50,50],"y":' . (($i - $i % 4) * 50) . ',"h":150,"angle":0,"x":' . (($i % 4) * 300) . ',"zindex":4}}';
        $i++;

        return $def;
    }

    private function getInterfaceDefinition($o) {
        $def = '';

        // First we need to create the needed data arrays
        $name = $o->name;

        $functions = array();
        foreach ($o->functions as $function) {
            $functions[] = $this->getModifierRepresentation($function->modifier) . $function->name . $this->getParamRepresentation($function->params);
        }

        // Create the node
        $def .= $this->createNode(
            $this->getUniqueId($o),
            array(
                'label' => $this->createInterfaceLabel($name, array(), $functions),
                'shape' => 'plaintext',
            )
        );

        // Create interface inheritance relation        
        if ($o->extends !== null) {
            // Check if we need an "external" interface node
            if (in_array($o->extends, $this->structure) !== true) {
                $def .= $this->getInterfaceDefinition($o->extends);
            }

            $def .= $this->createNodeRelation(
                $this->getUniqueId($o->extends),
                $this->getUniqueId($o),
                array(
                    'dir' => 'back',
                    'arrowtail' => 'empty',
                    'style' => 'solid'
                )
            );
        }

        return $def;
    }

    private function getModifierRepresentation($modifier) {
        return ($modifier === 'public')
            ? ('+')
            : (($modifier === 'protected')
                ? ('#')
                : ('-'));
    }

    private function getParamRepresentation($params) {
        if (count($params) === 0) {
            return '()';
        }

        $representation = '( ';
        for ($i = 0; $i < count($params); $i++) {
            if ($params[$i]->type !== null) {
                $representation .= $params[$i]->type . ' ';
            }

            $representation .= $params[$i]->name;
            if ($i < count($params) - 1) {
                $representation .= ', ';
            }
        }
        $representation .= ' )';

        return $representation;
    }

    private function getUniqueId($object) {
        return '"' . spl_object_hash($object) . '"';
    }

    private function createNode($name, $options) {
        $node = $name . " [";
        foreach ($options as $key => $value) {
            $node .= $key . '=' . $value . ' ';
        }
        $node .= "]\n";
        return $node;
    }

    private function createNodeRelation($node1, $node2, $options) {
        $relation = $node1 . ' -> ' . $node2 . ' [';
        foreach ($options as $key => $value) {
            $relation .= $key . '=' . $value . ' ';
        }
        $relation .= "]\n";
        return $relation;
    }

    private function createInterfaceLabel($name, $attributes, $functions) {
        // Start the table
        $label = '<<TABLE CELLSPACING="0" BORDER="0" ALIGN="LEFT">';

        // The title
        $label .= '<TR><TD BORDER="' . $this->options->style->interfaceTableBorder . '" ALIGN="CENTER" BGCOLOR="' . $this->options->style->interfaceTitleBackground . '"><FONT COLOR="' . $this->options->style->interfaceTitleColor . '" FACE="' . $this->options->style->interfaceTitleFont . '" POINT-SIZE="' . $this->options->style->interfaceTitleFontsize . '">' . $name . '</FONT></TD></TR>';

        // The attributes block
        $label .= '<TR><TD BORDER="' . $this->options->style->interfaceTableBorder . '" ALIGN="LEFT" BGCOLOR="' . $this->options->style->interfaceAttributesBackground . '">';
        if (count($attributes) === 0) {
            $label .= ' ';
        }
        foreach ($attributes as $attribute) {
            $label .= '<FONT COLOR="' . $this->options->style->interfaceAttributesColor . '" FACE="' . $this->options->style->interfaceAttributesFont . '" POINT-SIZE="' . $this->options->style->interfaceAttributesFontsize . '">' . $attribute . '</FONT><BR ALIGN="LEFT"/>';
        }
        $label .= '</TD></TR>';

        // The function block
        $label .= '<TR><TD BORDER="' . $this->options->style->interfaceTableBorder . '" ALIGN="LEFT" BGCOLOR="' . $this->options->style->interfaceFunctionsBackground . '">';
        if (count($functions) === 0) {
            $label .= ' ';
        }
        foreach ($functions as $function) {
            $label .= '<FONT COLOR="' . $this->options->style->interfaceFunctionsColor . '" FACE="' . $this->options->style->interfaceFunctionsFont . '" POINT-SIZE="' . $this->options->style->interfaceFunctionsFontsize . '">' . $function . '</FONT><BR ALIGN="LEFT"/>';
        }
        $label .= '</TD></TR>';

        // End the table
        $label .= '</TABLE>>';

        return $label;
    }

    private function createClassLabel($name, $attributes, $functions) {

        return $label;
    }
}

?>
