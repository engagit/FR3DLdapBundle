<?php

namespace FR3D\LdapBundle\Ldap;

use FR3D\LdapBundle\Driver\LdapDriverInterface;
use FR3D\LdapBundle\Hydrator\HydratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class LdapManager implements LdapManagerInterface
{
    protected $driver;
    protected $params = [];

    protected $paramSets = [];
    protected $ldapAttributes = [];
    protected $ldapUsernameAttr;

    /**
     * @var HydratorInterface
     */
    protected $hydrator;

    public function __construct(LdapDriverInterface $driver, HydratorInterface $hydrator, array $paramSets)
    {
        $this->driver = $driver;
        $this->hydrator = $hydrator;
        $this->paramSets = $paramSets;
    }

    public function findUserByUsername(string $username): ?UserInterface
    {
        if (!empty($this->params)) {
            return $this->findUserBy([$this->ldapUsernameAttr => $username]);
        } else {
            foreach ($this->paramSets as $paramSet) {
                $this->driver->init($paramSet['driver']);
                $this->params = $paramSet['user'];
                $this->setLdapAttr();
                $user = $this->findUserBy([$this->ldapUsernameAttr => $username]);
                if (false !== $user && $user instanceof UserInterface) {
                    return $user;
                }
                $this->params = [];
                $this->setLdapAttr();
            }
        }
        return null;
    }

    public function findUserBy(array $criteria): ?UserInterface
    {
        if (!empty($this->params)) {
            $filter = $this->buildFilter($criteria);
            $entries = $this->driver->search($this->params['baseDn'], $filter);
            if ($entries['count'] > 1) {
                throw new \Exception('This search can only return a single user');
            }
            if (0 === $entries['count']) {
                return null;
            }
            $user = $this->hydrator->hydrate($entries[0]);
            return $user;
        } else {
            foreach ($this->paramSets as $paramSet) {
                $this->driver->init($paramSet['driver']);
                $this->params = $paramSet['user'];
                $this->setLdapAttr();
                $user = $this->findUserBy($criteria);
                if (false !== $user && $user instanceof UserInterface) {
                    return $user;
                }
                $this->params = [];
                $this->setLdapAttr();
            }
        }
    }

    /**
     * Build Ldap filter.
     *
     * @param array  $criteria
     * @param string $condition
     *
     * @return string
     */
    protected function buildFilter(array $criteria, $condition = '&')
    {
        $filters = [];
        if (isset($this->params['filter'])) {
            $filters[] = $this->params['filter'];
        }
        foreach ($criteria as $key => $value) {
            $value = ldap_escape($value, '', LDAP_ESCAPE_FILTER);
            $filters[] = sprintf('(%s=%s)', $key, $value);
        }
        return sprintf('(%s%s)', $condition, implode($filters));
    }


    public function bind(UserInterface $user, string $password): bool
    {
        if (!empty($this->params)) {
            return $this->driver->bind($user, $password);
        } else {
            foreach ($this->paramSets as $paramSet) {
                $this->driver->init($paramSet['driver']);
                if (false !== $this->driver->bind($user, $password)) {
                    $this->params = $paramSet['user'];
                    $this->setLdapAttr();
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Sets temp Ldap attributes.
     */
    private function setLdapAttr()
    {
        if (isset($this->params['attributes'])) {
            $this->hydrator->setAttributeMap($this->params['attributes']);
            foreach ($this->params['attributes'] as $attr) {
                $this->ldapAttributes[] = $attr['ldap_attr'];
            }
            $this->ldapUsernameAttr = $this->ldapAttributes[0];
        } else {
            $this->ldapAttributes = [];
            $this->ldapUsernameAttr = null;
        }
    }
}
