<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* frontend/popup.twig */
class __TwigTemplate_d20b6f9782d3529bc16d947c58818bd3 extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 1
        if (($context["config_error"] ?? null)) {
            // line 2
            yield "\t<div class=\"gdh-rdv-config-error\" style=\"background:#fff3cd;border:1px solid #ffc107;border-radius:4px;padding:15px;margin:10px 0;color:#856404;\">
\t\t<strong>⚠️ Configuration requise</strong>
\t\t<p style=\"margin:10px 0 0 0;\">";
            // line 4
            yield ($context["config_error"] ?? null);
            yield "</p>
\t</div>
";
        } else {
            // line 7
            yield "\t<button type=\"button\" class=\"";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["class"] ?? null), "html", null, true);
            yield "\" data-gdh-rdv-open style=\"";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["style"] ?? null), "html", null, true);
            yield "\">
\t\t";
            // line 8
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["button_label"] ?? null), "html", null, true);
            yield "
\t</button>

\t<div id=\"gdh-rdv-popup\" class=\"gdh-rdv-popup\" style=\"display: none;\" data-post-type=\"";
            // line 11
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["current_post_type"] ?? null), "html", null, true);
            yield "\" data-post-id=\"";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["current_post_id"] ?? null), "html", null, true);
            yield "\">
\t<div class=\"gdh-rdv-popup-overlay\"></div>
\t<div
\t\tclass=\"gdh-rdv-popup-content\">
\t\t";
            // line 16
            yield "\t\t<div class=\"gdh-rdv-popup-header align-";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["title_align"] ?? null), "html", null, true);
            yield "\">
\t\t\t<h2>";
            // line 17
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["title_text"] ?? null), "html", null, true);
            yield "</h2>
\t\t\t<button type=\"button\" class=\"gdh-rdv-close\" data-gdh-rdv-close>&times;</button>
\t\t</div>

\t\t";
            // line 22
            yield "\t\t";
            yield from $this->loadTemplate("frontend/parts/progress.twig", "frontend/popup.twig", 22)->unwrap()->yield($context);
            // line 23
            yield "
\t\t";
            // line 25
            yield "\t\t<form
\t\t\tid=\"gdh-rdv-form\" class=\"gdh-rdv-form\">
\t\t\t";
            // line 28
            yield "\t\t\t<input type=\"hidden\" name=\"recipient_email\" value=\"";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["recipient_email"] ?? null), "html", null, true);
            yield "\" />
\t\t\t<input type=\"hidden\" name=\"recipient_name\" value=\"";
            // line 29
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["recipient_name"] ?? null), "html", null, true);
            yield "\" />
\t\t\t
\t\t\t";
            // line 32
            yield "\t\t\t";
            yield from $this->loadTemplate("frontend/steps/step-1-slots.twig", "frontend/popup.twig", 32)->unwrap()->yield($context);
            // line 33
            yield "
\t\t\t";
            // line 35
            yield "\t\t\t";
            yield from $this->loadTemplate("frontend/steps/step-2-slots.twig", "frontend/popup.twig", 35)->unwrap()->yield($context);
            // line 36
            yield "
\t\t\t";
            // line 38
            yield "\t\t\t";
            yield from $this->loadTemplate("frontend/steps/step-3-slots.twig", "frontend/popup.twig", 38)->unwrap()->yield($context);
            // line 39
            yield "
\t\t\t";
            // line 41
            yield "\t\t\t";
            yield from $this->loadTemplate("frontend/parts/form-actions.twig", "frontend/popup.twig", 41)->unwrap()->yield($context);
            // line 42
            yield "\t\t</form>

\t\t";
            // line 45
            yield "\t\t";
            yield from $this->loadTemplate("frontend/parts/success.twig", "frontend/popup.twig", 45)->unwrap()->yield($context);
            // line 46
            yield "\t</div>
</div>
";
        }
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "frontend/popup.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  136 => 46,  133 => 45,  129 => 42,  126 => 41,  123 => 39,  120 => 38,  117 => 36,  114 => 35,  111 => 33,  108 => 32,  103 => 29,  98 => 28,  94 => 25,  91 => 23,  88 => 22,  81 => 17,  76 => 16,  67 => 11,  61 => 8,  54 => 7,  48 => 4,  44 => 2,  42 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "frontend/popup.twig", "C:\\xampp\\htdocs\\www\\wordpress_blank\\wp-content\\plugins\\gdh-rdv-wp\\templates\\frontend\\popup.twig");
    }
}
