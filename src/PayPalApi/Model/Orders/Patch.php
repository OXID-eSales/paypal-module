<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * The JSON patch object to apply partial updates to resources.
 *
 * generated from:
 * MerchantsCommonComponentsSpecification-v1-schema-common_components-v3-schema-json-openapi-2.0-patch.json
 */
class Patch implements JsonSerializable
{
    use BaseModel;

    /** Depending on the target location reference, completes one of these functions:<ul><li><strong>The target location is an array index</strong>. Inserts a new value into the array at the specified index.</li><li><strong>The target location is an object parameter that does not already exist</strong>. Adds a new parameter to the object.</li><li><strong>The target location is an object parameter that does exist</strong>. Replaces that parameter's value.</li></ul>The <code>value</code> parameter defines the value to add. For more information, see <a href="https://tools.ietf.org/html/rfc6902#section-4.1">4.1. add</a>. */
    public const OP_ADD = 'add';

    /** Removes the value at the target location. For the operation to succeed, the target location must exist. For more information, see <a href="https://tools.ietf.org/html/rfc6902#section-4.2">4.2. remove</a>. */
    public const OP_REMOVE = 'remove';

    /** Replaces the value at the target location with a new value. The operation object must contain a <code>value</code> parameter that defines the replacement value. For the operation to succeed, the target location must exist. For more information, see <a href="https://tools.ietf.org/html/rfc6902#section-4.3">4.3. replace</a>. */
    public const OP_REPLACE = 'replace';

    /** Removes the value at a specified location and adds it to the target location. The operation object must contain a <code>from</code> parameter, which is a string that contains a JSON pointer value that references the location in the target document from which to move the value. For the operation to succeed, the <code>from</code> location must exist. For more information, see <a href="https://tools.ietf.org/html/rfc6902#section-4.4">4.4. move</a>. */
    public const OP_MOVE = 'move';

    /** Copies the value at a specified location to the target location. The operation object must contain a <code>from</code> parameter, which is a string that contains a JSON pointer value that references the location in the target document from which to copy the value. For the operation to succeed, the <code>from</code> location must exist. For more information, see <a href="https://tools.ietf.org/html/rfc6902#section-4.5">4.5. copy</a>. */
    public const OP_COPY = 'copy';

    /** Tests that a value at the target location is equal to a specified value. The operation object must contain a <code>value</code> parameter that defines the value to compare to the target location's value. For the operation to succeed, the target location must be equal to the <code>value</code> value. For test, <code>equal</code> indicates that the value at the target location and the value that <code>value</code> defines are of the same JSON type. The data type of the value determines how equality is defined:<table><thead align="left"><tr><th>Type</th><th>Considered equal if both values</th></tr></thead><tbody align="left"><tr><td><strong>strings</strong></td><td>Contain the same number of Unicode characters and their code points are byte-by-byte equal.</td></tr><tr><td><strong>numbers</strong></td><td>Are numerically equal.</td></tr><tr><td><strong>arrays</strong></td><td>Contain the same number of values, and each value is equal to the value at the corresponding position in the other array, by using these type-specific rules.</td></tr><tr><td><strong>objects</strong></td><td>Contain the same number of parameters, and each parameter is equal to a parameter in the other object, by comparing their keys (as strings) and their values (by using these type-specific rules).</td></tr><tr><td><strong>literals (<code>false</code>, <code>true</code>, and <code>null</code>)</strong></td><td>Are the same. The comparison is a logical comparison. For example, whitespace between the parameter values of an array is not significant. Also, ordering of the serialization of object parameters is not significant.</td></tr></tbody></table>For more information, see <a href="https://tools.ietf.org/html/rfc6902#section-4.6">4.6. test</a>. */
    public const OP_TEST = 'test';

    /**
     * The operation.
     *
     * use one of constants defined in this class to set the value:
     * @see OP_ADD
     * @see OP_REMOVE
     * @see OP_REPLACE
     * @see OP_MOVE
     * @see OP_COPY
     * @see OP_TEST
     * @var string
     */
    public $op;

    /**
     * The <a href="https://tools.ietf.org/html/rfc6901">JSON Pointer</a> to the target document location at which to
     * complete the operation.
     *
     * @var string | null
     */
    public $path;

    /**
     * The value to apply. The <code>remove</code> operation does not require a value.
     *
     * @var number|integer|string|boolean|null|array|object | null
     */
    public $value;

    /**
     * The <a href="https://tools.ietf.org/html/rfc6901">JSON Pointer</a> to the target document location from which
     * to move the value. Required for the <code>move</code> operation.
     *
     * @var string | null
     */
    public $from;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        Assert::notNull($this->op, "op in Patch must not be NULL $within");
    }

    private function map(array $data)
    {
        if (isset($data['op'])) {
            $this->op = $data['op'];
        }
        if (isset($data['path'])) {
            $this->path = $data['path'];
        }
        if (isset($data['value'])) {
            $this->value = $data['value'];
        }
        if (isset($data['from'])) {
            $this->from = $data['from'];
        }
    }

    public function __construct(array $data = null)
    {
        if (isset($data)) {
            $this->map($data);
        }
    }
}
