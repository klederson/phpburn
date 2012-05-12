<?php
namespace PhpBURN\Spices\ACL;

\PhpBURN::loadSpice('acl',array('1.0'));

############################################
# Create Settings
############################################

/**
 * ACL Spice Settings
 */
$aclSettings = array(
    /**
     * This defines the default visibility based on method/action type
     * public, protected and private
     */
    'defaultVisibile' => array(
        'public'
//        'protected',
//        'private'
    ),

    /**
     * This defines the default permission when @acldefault is not set for the
     * Controller or Action.
     *
     * Note you can override this anytime ( in @acldefault at Controller or Action ).
     */
    'defaultPermission' => 'allow',

    /**
     * This defines the default type of each action
     * read, write, exclude and unknown ( recommended )
     *
     * This typing only have logistic function it does not affect the code
     */
    'defaultType' => 'unknown',

    'authInfo' => array(
        'allowedMethods' => &$_SESSION[PHPBURN_SESSIONNAME]['userInfo']['allowedMethods']
    )
);

############################################
# Define ACL configuration
############################################
PhpBURN_ACL::setConfig($aclSettings);
PhpBURN_ACL_Control::generateRules();


############################################
# callBack functions
############################################
$callBack = array(
    'granted' => function() {
        return true;
    },

    'denied' => function() {
        die("Your access to this area has been denied. You've been a bad bad dog!");
    }
);

PhpBURN_ACL::setCallBack($callBack);

############################################
# Add Controller onCallActionBefore to be
# executed before each controller call
############################################
\PhpBURN_ControllerConfig::addOnCallActionBefore(
        'phpburn_spice_acl',

        function($controllerName, $action, $parms) {
            PhpBURN_ACL::checkPermissions($controllerName, $action, $parms);
        }
);


?>
