<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

interface patchInterface
{
    /**
     * Returns the release version.
     *
     * @return string
     */
    public function get_release();

    /**
     * Returns whether the patch concerns the Application Box or
     * the Data Box.
     *
     *  It accepts base::APPLICATION_BOX or base::DATA_BOX value.
     *
     * @return array
     */
    public function concern();

    /**
     * Tells whether the patch must be run after the others or not.
     *
     * @return boolean
     */
    public function require_all_upgrades();

    /**
     * Apply patch.
     *
     * @param base        $base The Application Box or the Data Boxes where the patch is applied.
     * @param Application $app
     *
     * @return boolean returns true if the patch succeed.
     */
    public function apply(base $base, Application $app);

    /**
     * Returns doctrine migrations needed for the patch.
     *
     * @return array
     */
    public function getDoctrineMigrations();
}
