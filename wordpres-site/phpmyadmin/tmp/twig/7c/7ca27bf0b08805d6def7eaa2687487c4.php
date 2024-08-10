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

/* preferences/manage/error.twig */
class __TwigTemplate_f651d0c9a059d8e8d8bb9df538641b4d extends Template
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
        echo $this->env->getFilter('error')->getCallable()(_gettext("Configuration contains incorrect data for some fields."));
        echo "
<div class=\"config-form\">
    ";
        // line 3
        echo ($context["form_errors"] ?? null);
        echo "
</div>
<form action=\"";
        // line 5
        echo PhpMyAdmin\Url::getFromRoute("/preferences/manage");
        echo "\" method=\"post\" class=\"disableAjax\">
    ";
        // line 6
        echo PhpMyAdmin\Url::getHiddenInputs();
        echo "
    <input type=\"hidden\" name=\"json\" value=\"";
        // line 7
        echo twig_escape_filter($this->env, ($context["json"] ?? null), "html", null, true);
        echo "\">
    <input type=\"hidden\" name=\"fix_errors\" value=\"1\">
    ";
        // line 9
        if ( !twig_test_empty(($context["import_merge"] ?? null))) {
            // line 10
            echo "        <input type=\"hidden\" name=\"import_merge\" value=\"1\">
    ";
        }
        // line 12
        echo "    ";
        if (($context["return_url"] ?? null)) {
            // line 13
            echo "        <input type=\"hidden\" name=\"return_url\" value=\"";
            echo twig_escape_filter($this->env, ($context["return_url"] ?? null), "html", null, true);
            echo "\">
    ";
        }
        // line 15
        echo "    <p>
        ";
echo _gettext("Do you want to import remaining settings?");
        // line 17
        echo "    </p>
    <input class=\"btn btn-secondary\" type=\"submit\" name=\"submit_import\" value=\"";
        // line 18
        echo twig_escape_filter($this->env, _gettext("Yes"), "html", null, true);
        echo "\">
    <input class=\"btn btn-secondary\" type=\"submit\" name=\"submit_ignore\" value=\"";
        // line 19
        echo twig_escape_filter($this->env, _gettext("No"), "html", null, true);
        echo "\">
</form>
";
    }

    public function getTemplateName()
    {
        return "preferences/manage/error.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  86 => 19,  82 => 18,  79 => 17,  75 => 15,  69 => 13,  66 => 12,  62 => 10,  60 => 9,  55 => 7,  51 => 6,  47 => 5,  42 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "preferences/manage/error.twig", "/var/www/html/phpmyadmin/templates/preferences/manage/error.twig");
    }
}
