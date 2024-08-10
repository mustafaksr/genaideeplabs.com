<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* database/routines/row.twig */
class __TwigTemplate_e9da5f250140c29b16a72903fe60aa09 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo "<tr";
        if ( !twig_test_empty(($context["row_class"] ?? null))) {
            echo " class=\"";
            echo twig_escape_filter($this->env, ($context["row_class"] ?? null), "html", null, true);
            echo "\"";
        }
        echo " data-filter-row=\"";
        echo twig_escape_filter($this->env, twig_upper_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["routine"] ?? null), "name", [], "any", false, false, false, 1)), "html", null, true);
        echo "\">
  <td>
    <input type=\"checkbox\" class=\"checkall\" name=\"item_name[]\" value=\"";
        // line 3
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["routine"] ?? null), "name", [], "any", false, false, false, 3), "html", null, true);
        echo "\">
  </td>
  <td>
    <span class=\"drop_sql hide\">";
        // line 6
        echo twig_escape_filter($this->env, ($context["sql_drop"] ?? null), "html", null, true);
        echo "</span>
    <strong>";
        // line 7
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["routine"] ?? null), "name", [], "any", false, false, false, 7), "html", null, true);
        echo "</strong>
  </td>
  <td>
    ";
        // line 10
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["routine"] ?? null), "type", [], "any", false, false, false, 10), "html", null, true);
        echo "
  </td>
  <td dir=\"ltr\">
    ";
        // line 13
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["routine"] ?? null), "returns", [], "any", false, false, false, 13), "html", null, true);
        echo "
  </td>
  <td>
    ";
        // line 16
        if (($context["has_edit_privilege"] ?? null)) {
            // line 17
            echo "      <a class=\"ajax edit_anchor\" href=\"";
            echo PhpMyAdmin\Url::getFromRoute("/database/routines", ["db" =>             // line 18
($context["db"] ?? null), "table" =>             // line 19
($context["table"] ?? null), "edit_item" => true, "item_name" => twig_get_attribute($this->env, $this->source,             // line 21
($context["routine"] ?? null), "name", [], "any", false, false, false, 21), "item_type" => twig_get_attribute($this->env, $this->source,             // line 22
($context["routine"] ?? null), "type", [], "any", false, false, false, 22)]);
            // line 23
            echo "\">
        ";
            // line 24
            echo PhpMyAdmin\Html\Generator::getIcon("b_edit", _gettext("Edit"));
            echo "
      </a>
    ";
        } else {
            // line 27
            echo "      ";
            echo PhpMyAdmin\Html\Generator::getIcon("bd_edit", _gettext("Edit"));
            echo "
    ";
        }
        // line 29
        echo "  </td>
  <td>
    ";
        // line 31
        if ((($context["has_execute_privilege"] ?? null) &&  !twig_test_empty(($context["execute_action"] ?? null)))) {
            // line 32
            echo "      ";
            if ((($context["execute_action"] ?? null) == "execute_routine")) {
                // line 33
                echo "        <a class=\"ajax exec_anchor\" href=\"";
                echo PhpMyAdmin\Url::getFromRoute("/database/routines", ["db" => ($context["db"] ?? null), "table" => ($context["table"] ?? null)]);
                echo "\" data-post=\"";
                echo PhpMyAdmin\Url::getCommon(["execute_routine" => true, "item_name" => twig_get_attribute($this->env, $this->source,                 // line 35
($context["routine"] ?? null), "name", [], "any", false, false, false, 35), "item_type" => twig_get_attribute($this->env, $this->source,                 // line 36
($context["routine"] ?? null), "type", [], "any", false, false, false, 36)], "");
                // line 37
                echo "\">
          ";
                // line 38
                echo PhpMyAdmin\Html\Generator::getIcon("b_nextpage", _gettext("Execute"));
                echo "
        </a>
      ";
            } else {
                // line 41
                echo "        <a class=\"ajax exec_anchor\" href=\"";
                echo PhpMyAdmin\Url::getFromRoute("/database/routines", ["db" =>                 // line 42
($context["db"] ?? null), "table" =>                 // line 43
($context["table"] ?? null), "execute_dialog" => true, "item_name" => twig_get_attribute($this->env, $this->source,                 // line 45
($context["routine"] ?? null), "name", [], "any", false, false, false, 45), "item_type" => twig_get_attribute($this->env, $this->source,                 // line 46
($context["routine"] ?? null), "type", [], "any", false, false, false, 46)]);
                // line 47
                echo "\">
          ";
                // line 48
                echo PhpMyAdmin\Html\Generator::getIcon("b_nextpage", _gettext("Execute"));
                echo "
        </a>
      ";
            }
            // line 51
            echo "    ";
        } else {
            // line 52
            echo "      ";
            echo PhpMyAdmin\Html\Generator::getIcon("bd_nextpage", _gettext("Execute"));
            echo "
    ";
        }
        // line 54
        echo "  </td>
  <td>
    ";
        // line 56
        if (($context["has_export_privilege"] ?? null)) {
            // line 57
            echo "      <a class=\"ajax export_anchor\" href=\"";
            echo PhpMyAdmin\Url::getFromRoute("/database/routines", ["db" =>             // line 58
($context["db"] ?? null), "table" =>             // line 59
($context["table"] ?? null), "export_item" => true, "item_name" => twig_get_attribute($this->env, $this->source,             // line 61
($context["routine"] ?? null), "name", [], "any", false, false, false, 61), "item_type" => twig_get_attribute($this->env, $this->source,             // line 62
($context["routine"] ?? null), "type", [], "any", false, false, false, 62)]);
            // line 63
            echo "\">
        ";
            // line 64
            echo PhpMyAdmin\Html\Generator::getIcon("b_export", _gettext("Export"));
            echo "
      </a>
    ";
        } else {
            // line 67
            echo "      ";
            echo PhpMyAdmin\Html\Generator::getIcon("bd_export", _gettext("Export"));
            echo "
    ";
        }
        // line 69
        echo "  </td>
  <td>
    ";
        // line 71
        echo PhpMyAdmin\Html\Generator::linkOrButton(PhpMyAdmin\Url::getFromRoute("/sql"), ["db" =>         // line 74
($context["db"] ?? null), "table" =>         // line 75
($context["table"] ?? null), "sql_query" =>         // line 76
($context["sql_drop"] ?? null), "goto" => PhpMyAdmin\Url::getFromRoute("/database/routines", ["db" =>         // line 77
($context["db"] ?? null)])], PhpMyAdmin\Html\Generator::getIcon("b_drop", _gettext("Drop")), ["class" => "ajax drop_anchor"]);
        // line 81
        echo "
  </td>
</tr>
";
    }

    public function getTemplateName()
    {
        return "database/routines/row.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  189 => 81,  187 => 77,  186 => 76,  185 => 75,  184 => 74,  183 => 71,  179 => 69,  173 => 67,  167 => 64,  164 => 63,  162 => 62,  161 => 61,  160 => 59,  159 => 58,  157 => 57,  155 => 56,  151 => 54,  145 => 52,  142 => 51,  136 => 48,  133 => 47,  131 => 46,  130 => 45,  129 => 43,  128 => 42,  126 => 41,  120 => 38,  117 => 37,  115 => 36,  114 => 35,  110 => 33,  107 => 32,  105 => 31,  101 => 29,  95 => 27,  89 => 24,  86 => 23,  84 => 22,  83 => 21,  82 => 19,  81 => 18,  79 => 17,  77 => 16,  71 => 13,  65 => 10,  59 => 7,  55 => 6,  49 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "database/routines/row.twig", "/var/www/html/phpmyadmin/templates/database/routines/row.twig");
    }
}
