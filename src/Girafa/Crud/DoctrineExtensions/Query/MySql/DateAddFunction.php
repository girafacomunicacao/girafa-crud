<?php
namespace Girafa\Crud\DoctrineExtensions\Query\MySql;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;

/**
 * DateAdd ::=
 *     "DATE_ADD" "(" ArithmeticPrimary ", INTERVAL" ArithmeticPrimary Identifier ")"
 * 
 * @link    www.doctrine-project.org
 * @since   2.0 
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class DateAddFunction extends FunctionNode
{
    public $firstDateExpression = null;
    public $intervalExpression = null;
    public $unit = null;

	/**
	 * @override
	 */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->firstDateExpression = $parser->ArithmeticPrimary();

        $parser->match(Lexer::T_COMMA);
        $parser->match(Lexer::T_IDENTIFIER);

        $this->intervalExpression = $parser->ArithmeticPrimary();

        $parser->match(Lexer::T_IDENTIFIER);

        /* @var $lexer Lexer */
        $lexer = $parser->getLexer();
        $this->unit = $lexer->token['value'];

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

	/**
	 * @override
	 */
    public function getSql(SqlWalker $sqlWalker)
    {
        return 'DATE_ADD(' .
            $this->firstDateExpression->dispatch($sqlWalker) . ', INTERVAL ' .
            $this->intervalExpression->dispatch($sqlWalker) . ' ' . $this->unit .
        ')';
    }
}
