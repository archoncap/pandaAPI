<?php
/**
 * 验证器
 * 简单的数据验证
 */

namespace PandaAPI\Validation;

class Validator
{
    /**
     * @var array 验证数据
     */
    protected $data;

    /**
     * @var array 验证规则
     */
    protected $rules = [];

    /**
     * @var array 自定义消息
     */
    protected $messages = [];

    /**
     * @var array 错误信息
     */
    protected $errors = [];

    /**
     * @var array 验证后的数据
     */
    protected $validated = [];

    /**
     * 构造函数
     */
    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    /**
     * 执行验证
     */
    public function validate(): bool
    {
        $this->errors = [];
        $this->validated = [];

        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->getValue($field);

            foreach ($rules as $rule) {
                $this->validateRule($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * 验证单条规则
     */
    protected function validateRule(string $field, $value, string $rule): void
    {
        // 解析规则和参数
        $params = [];
        if (strpos($rule, ':') !== false) {
            [$rule, $paramString] = explode(':', $rule);
            $params = explode(',', $paramString);
        }

        $method = 'validate' . ucfirst($rule);
        
        if (method_exists($this, $method)) {
            $passed = $this->$method($field, $value, $params);
            
            if (!$passed) {
                $this->addError($field, $rule, $params);
            }
        }
    }

    /**
     * 获取字段值
     */
    protected function getValue(string $field)
    {
        return $this->data[$field] ?? null;
    }

    /**
     * 添加错误
     */
    protected function addError(string $field, string $rule, array $params = []): void
    {
        $key = "{$field}.{$rule}";
        
        if (isset($this->messages[$key])) {
            $message = $this->messages[$key];
        } else {
            $message = $this->getDefaultMessage($field, $rule, $params);
        }
        
        $this->errors[$field][] = $message;
    }

    /**
     * 获取默认消息
     */
    protected function getDefaultMessage(string $field, string $rule, array $params = []): string
    {
        $messages = [
            'required' => "The {$field} field is required.",
            'email' => "The {$field} must be a valid email address.",
            'min' => "The {$field} must be at least {$params[0]} characters.",
            'max' => "The {$field} must not exceed {$params[0]} characters.",
            'numeric' => "The {$field} must be a number.",
            'integer' => "The {$field} must be an integer.",
            'string' => "The {$field} must be a string.",
            'array' => "The {$field} must be an array.",
            'in' => "The selected {$field} is invalid.",
            'not_in' => "The selected {$field} is invalid.",
            'between' => "The {$field} must be between {$params[0]} and {$params[1]}.",
            'min_value' => "The {$field} must be at least {$params[0]}.",
            'max_value' => "The {$field} must not exceed {$params[0]}.",
            'regex' => "The {$field} format is invalid.",
            'url' => "The {$field} must be a valid URL.",
            'ip' => "The {$field} must be a valid IP address.",
            'alpha' => "The {$field} may only contain letters.",
            'alpha_num' => "The {$field} may only contain letters and numbers.",
            'date' => "The {$field} is not a valid date.",
            'confirmed' => "The {$field} confirmation does not match.",
            'unique' => "The {$field} has already been taken.",
            'exists' => "The selected {$field} is invalid.",
        ];

        return $messages[$rule] ?? "Validation failed for {$field}.";
    }

    /**
     * 验证：必填
     */
    protected function validateRequired(string $field, $value, array $params = []): bool
    {
        if (is_null($value)) {
            return false;
        }
        
        if (is_string($value) && trim($value) === '') {
            return false;
        }
        
        if (is_array($value) && count($value) === 0) {
            return false;
        }
        
        return true;
    }

    /**
     * 验证：邮箱
     */
    protected function validateEmail(string $field, $value, array $params = []): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * 验证：最小长度/值
     */
    protected function validateMin(string $field, $value, array $params = []): bool
    {
        $min = $params[0] ?? 0;
        
        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }
        
        if (is_numeric($value)) {
            return $value >= $min;
        }
        
        if (is_array($value)) {
            return count($value) >= $min;
        }
        
        return false;
    }

    /**
     * 验证：最大长度/值
     */
    protected function validateMax(string $field, $value, array $params = []): bool
    {
        $max = $params[0] ?? 0;
        
        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }
        
        if (is_numeric($value)) {
            return $value <= $max;
        }
        
        if (is_array($value)) {
            return count($value) <= $max;
        }
        
        return false;
    }

    /**
     * 验证：数字
     */
    protected function validateNumeric(string $field, $value, array $params = []): bool
    {
        return is_numeric($value);
    }

    /**
     * 验证：整数
     */
    protected function validateInteger(string $field, $value, array $params = []): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * 验证：字符串
     */
    protected function validateString(string $field, $value, array $params = []): bool
    {
        return is_string($value);
    }

    /**
     * 验证：数组
     */
    protected function validateArray(string $field, $value, array $params = []): bool
    {
        return is_array($value);
    }

    /**
     * 验证：在列表中
     */
    protected function validateIn(string $field, $value, array $params = []): bool
    {
        return in_array($value, $params);
    }

    /**
     * 验证：不在列表中
     */
    protected function validateNotIn(string $field, $value, array $params = []): bool
    {
        return !in_array($value, $params);
    }

    /**
     * 验证：范围
     */
    protected function validateBetween(string $field, $value, array $params = []): bool
    {
        $min = $params[0] ?? 0;
        $max = $params[1] ?? 0;
        
        if (is_string($value)) {
            $len = mb_strlen($value);
            return $len >= $min && $len <= $max;
        }
        
        if (is_numeric($value)) {
            return $value >= $min && $value <= $max;
        }
        
        return false;
    }

    /**
     * 验证：正则
     */
    protected function validateRegex(string $field, $value, array $params = []): bool
    {
        $pattern = $params[0] ?? '';
        return preg_match($pattern, $value) === 1;
    }

    /**
     * 验证：URL
     */
    protected function validateUrl(string $field, $value, array $params = []): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * 验证：IP
     */
    protected function validateIp(string $field, $value, array $params = []): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * 验证：字母
     */
    protected function validateAlpha(string $field, $value, array $params = []): bool
    {
        return preg_match('/^[\pL\pM]+$/u', $value) === 1;
    }

    /**
     * 验证：字母数字
     */
    protected function validateAlphaNum(string $field, $value, array $params = []): bool
    {
        return preg_match('/^[\pL\pM\pN]+$/u', $value) === 1;
    }

    /**
     * 验证：日期
     */
    protected function validateDate(string $field, $value, array $params = []): bool
    {
        $format = $params[0] ?? 'Y-m-d';
        $d = \DateTime::createFromFormat($format, $value);
        return $d && $d->format($format) === $value;
    }

    /**
     * 验证：确认
     */
    protected function validateConfirmed(string $field, $value, array $params = []): bool
    {
        $confirmationField = "{$field}_confirmation";
        return isset($this->data[$confirmationField]) && $value === $this->data[$confirmationField];
    }

    /**
     * 检查是否验证失败
     */
    public function fails(): bool
    {
        return !$this->validate();
    }

    /**
     * 检查是否验证通过
     */
    public function passes(): bool
    {
        return $this->validate();
    }

    /**
     * 获取错误信息
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * 获取第一条错误
     */
    public function firstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        return null;
    }

    /**
     * 获取验证后的数据
     */
    public function validated(): array
    {
        $validated = [];
        
        foreach ($this->rules as $field => $ruleString) {
            $validated[$field] = $this->getValue($field);
        }
        
        return $validated;
    }
}
