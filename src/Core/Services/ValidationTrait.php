<?php
namespace  Pionia\Core\Services;

use Nette\Utils\Validators;
use Pionia\Exceptions\InvalidDataException;
/**
 */
trait ValidationTrait
{
    public  string $phone_pattern = "/^[+]{1}(?:[0-9\-\\(\\)\\/.]\s?){6,15}[0-9]{1}$/";
    public  string $password_pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*(_|[^\w])).+$/";
    public  string $ip_pattern = "/^(\d{1,3}\.){3}\d{1,3}$/";
    public  string $slug_pattern = "/^[a-z0-9-]+$/";
    private bool $throwsExceptions = true;

    public function __construct($throws = true)
    {
       $this->throwsExceptions = $throws;
    }

    /**
     * Use this to cover scenarios this contract does not cover
     * @param string $regex - The regular expression to check against
     * @param mixed $value - The value to check
     * @param string|null $message - The message to throw if the value is invalid and we are in the exceptions mode
     * @return bool|int
     * @throws InvalidDataException
     */
    public function validate(string $regex, mixed $value, ?string $message = 'Invalid data'): bool|int
    {
        $checker = filter_var($value, FILTER_VALIDATE_REGEXP,  ['options' => ['regexp' => $regex]]);
        if (!$checker && $this->throwsExceptions) {
            throw new InvalidDataException($message);
        }
        return $checker;
    }

    /**
     * Validates emails of all formats
     * @param string $email The email address we're currently testing in the core.
     * @throws InvalidDataException
     */
    public  function asEmail(string $email): bool|int
    {
        return Validators::isEmail($email) ?: throw new InvalidDataException('Invalid email address');
    }

    /**
     * Will only validate international numbers if the code is provided, otherwise, will validate local only
     *
     * @param string $phone The phone number we are testing
     * @param string|null $code International country that you want to check against
     * @param string|null $regex Your custom regular expression we can depend on instead.
     * @return bool|int
     * @throws InvalidDataException
     */
    public  function asInternationalPhone(string $phone, ?string $code = null, ?string $regex = null): bool|int
    {
        // we have the regex but no code
        if (!$code){
            return $this->validate($regex ?? $this->phone_pattern, $phone);
        }

        $copy = $phone;
        if (!str_starts_with($copy, $code)){
            throw new InvalidDataException('Invalid phone number, must start with '.$code);
        }
        return $this->validate($regex ?? $this->phone_pattern, $copy, 'Invalid phone number');
    }

    /**
     * Validates the rules as follows
     *  - At least one integer
     *  - At least one lowercase alpha letter
     *  - At least one Uppercase alpha letter
     *  - At least one special character
     *
     * @param mixed $password The password string we are testing, must be raw, not hashed
     * @param string|null $regex Your custom regular expression we can depend on instead.
     * @throws InvalidDataException
     */
    public  function asPassword(mixed $password, ?string $regex = null): bool|int
    {
        return $this->validate($regex ?? $this->password_pattern, $password, 'Week Password');
    }

    /**
     * @param mixed $number The number we are checking
     * @return bool
     * @throws InvalidDataException
     */
    public function asNumber(mixed $number): bool
    {
        return Validators::isNumber($number) ?? throw new InvalidDataException('Invalid Number');
    }

    /**
     * @throws InvalidDataException
     */
    public function asNumeric($number): bool|int
    {
        return Validators::isNumeric($number) ?: throw new InvalidDataException('Invalid Numeric');
    }

    /**
     * @throws InvalidDataException
     */
    public function asNumericInt($number): bool|int
    {
        if (Validators::isNumericInt($number)){
            return true;
        }
        throw new InvalidDataException('Invalid Numeric Integer');
    }

    /**
     * @param mixed $url The url string we are testing
     * @param string|null $regex Your custom regular expression we can depend on instead.
     * @return bool|int
     * @throws InvalidDataException
     */
    public  function asUrl(mixed $url, ?string $regex = null): bool|int
    {
        if (Validators::isUrl($url)){
            return true;
        }
        throw new InvalidDataException('Invalid URL');
    }

    /**
     * @param mixed $ip The Ip address we're checking.
     * @param string|null $regex Your custom regular expression we can depend on instead.
     * @return bool|int
     * @throws InvalidDataException
     */
    public  function asIp(mixed $ip, ?string $regex = null): bool|int
    {
        if ($regex) {
            return $this->validate($regex ?? $this->ip_pattern, $ip, 'Invalid IP address');
        }
        return $this->_validateFilter($ip, FILTER_VALIDATE_IP, 'Invalid IP address');
    }

    /**
     * @param mixed $mac The mac address we are checking
     * @param string|null $regex Your custom regular expression we can depend on instead.
     * @return bool|int
     * @throws InvalidDataException
     */
    public  function asMac(mixed $mac, ?string $regex = null): bool|int
    {
        if ($regex) {
            return $this->validate($regex, $mac, 'Invalid MAC Address');
        }

        return $this->_validateFilter($mac, FILTER_VALIDATE_MAC, 'Invalid Mac Address');
    }

    /**
     * @param mixed $domain The domain we are testing its validity
     * @param string|null $regex Your custom regular expression we can depend on instead.
     * @return bool|int
     * @throws InvalidDataException
     */
    public function asDomain(mixed $domain, ?string $regex = null): bool|int
    {
        if ($regex){
            return $this->validate($regex, $domain, 'Invalid domain');
        }

        return $this->_validateFilter($domain, FILTER_VALIDATE_DOMAIN, 'Invalid Domain');
    }


    /**
     * @param mixed $slug The slug string we are testing
     * @param string|null $regex Your custom regular expression we can depend on instead.
     *
     * @example ```
     *      $slug = 'fsjkfjshfsjk-skdhfkjdfsj-skdjfhjskdf'; // valid slug
     *      $slug2 = 'sfksdfsdskljfhsdhjkfhsdsfsdfsfsd'; // valid slug
     *      $slug3 = 'dkfl ksjfhsdk/skjdfsk%'; // invalid slug
     * @return bool|int
     * @throws InvalidDataException
     */
    public  function asSlug(mixed $slug, ?string $regex = null): bool|int
    {
        return $this->validate($regex ?? $this->slug_pattern, $slug, 'Invalid slug');
    }


    /**
     * Internal validator based on PHP filter_var validations
     * @param mixed $value
     * @param $filterType
     * @param string $message
     * @return int|bool
     * @throws InvalidDataException
     */
    private function _validateFilter(mixed $value, $filterType, string $message = 'Invalid Data'): int | bool
    {
        $checker = filter_var($value, $filterType);
        if (!$checker && $this->throwsExceptions){
            throw new InvalidDataException($message);
        }
        return $checker == $value;
    }

    /**
     * @throws InvalidDataException
     */
    private function shouldBe($value, $expected): bool
    {
        return Validators::is($value, $expected) ?:throw new InvalidDataException('Invalid data');
    }

    /**
     * @throws InvalidDataException
     */
    public function allShouldBe(iterable $value, string $expected)
    {
        return Validators::everyIs($value, $expected) ?: throw new InvalidDataException('Invalid data');
    }
}
