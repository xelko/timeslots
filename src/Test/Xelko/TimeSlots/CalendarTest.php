<?php

namespace Xelko\TimeSlots;

use Xelko\TimeSlots\Calendar;

/**
 * @group TimeSlots
 */
class CalendarTest extends \PHPUnit_Framework_TestCase
{

    function testNew_GetPeriods()
    {
        $obj = new Calendar();

        // ********************************************

        $res = $obj->getPeriodsOfDay(new \DateTime("2014-01-01 12:20:00"));
        $resOk = array();
        $this->assertEquals($resOk, $res, "getPeriodsOfDay()");

        // ********************************************

        $res = $obj->getPeriodsOfDays(new \DateTime("2014-01-01 12:20:00"), new \DateTime("2014-01-03 12:20:00"));
        $resOk = Array(
          '2014-01-01' => Array(),
          '2014-01-02' => Array(),
          '2014-01-03' => Array(),
        );
        $this->assertEquals($resOk, $res, "getPeriodsOfDays() sans ajustement");

        // ********************************************

        $res = $obj->getPeriodsOfDays(new \DateTime("2014-01-01 12:20:00"), new \DateTime("2014-01-03 12:20:00"), true);
        $resOk = Array(
          '2014-01-01' => Array(),
          '2014-01-02' => Array(),
          '2014-01-03' => Array(),
        );
        $this->assertEquals($resOk, $res, "getPeriodsOfDays() avec ajustement ");
    }

    function testSimpleOpenRules_GetPeriods()
    {
        $obj = new Calendar();
        $obj->addOpenRules([
          "p=600-700,800-900",
        ]);

        // ********************************************

        $res = $obj->getPeriodsOfDay(new \DateTime("2014-01-01 12:20:00"));
        $resOk = array(
          array('begin' => 600, 'end' => 700),
          array('begin' => 800, 'end' => 900),
        );
        $this->assertEquals($resOk, $res, "getPeriodsOfDay()");

        // ********************************************

        $res = $obj->getPeriodsOfDays(new \DateTime("2014-01-01 12:20:00"), new \DateTime("2014-01-03 12:20:00"));
        $resOk = Array(
          '2014-01-01' => array(
            array('begin' => 600, 'end' => 700),
            array('begin' => 800, 'end' => 900),
          ),
          '2014-01-02' => array(
            array('begin' => 600, 'end' => 700),
            array('begin' => 800, 'end' => 900),
          ),
          '2014-01-03' => array(
            array('begin' => 600, 'end' => 700),
            array('begin' => 800, 'end' => 900),
          ),
        );
        $this->assertEquals($resOk, $res, "getPeriodsOfDays() sans ajustement");

        // ********************************************

        $res = $obj->getPeriodsOfDays(new \DateTime("2014-01-01 12:20:00"), new \DateTime("2014-01-03 12:20:00"), true);
        $resOk = Array(
          '2014-01-01' => array(
            array('begin' => 800, 'end' => 900),
          ),
          '2014-01-02' => array(
            array('begin' => 600, 'end' => 700),
            array('begin' => 800, 'end' => 900),
          ),
          '2014-01-03' => array(
            array('begin' => 600, 'end' => 700),
          ),
        );
        $this->assertEquals($resOk, $res, "getPeriodsOfDays() avec ajustement ");
    }

    function testSimpleOpenCloseRules_GetPeriods()
    {
        $obj = new Calendar();
        $obj->addOpenRules([
          "p=600-700",
          "p=800-900",
        ]);
        $obj->addCloseRules([
          "p=650-850",
          "p=1000-1100",
        ]);

        // ********************************************

        $res = $obj->getPeriodsOfDay(new \DateTime("2014-01-01 12:20:00"));
        $resOk = array(
          array('begin' => 600, 'end' => 650),
          array('begin' => 850, 'end' => 900),
        );
        $this->assertEquals($resOk, $res, "getPeriodsOfDay()");

        // ********************************************

        $res = $obj->getPeriodsOfDays(new \DateTime("2014-01-01 12:20:00"), new \DateTime("2014-01-03 12:20:00"));
        $resOk = Array(
          '2014-01-01' => array(
            array('begin' => 600, 'end' => 650),
            array('begin' => 850, 'end' => 900),
          ),
          '2014-01-02' => array(
            array('begin' => 600, 'end' => 650),
            array('begin' => 850, 'end' => 900),
          ),
          '2014-01-03' => array(
            array('begin' => 600, 'end' => 650),
            array('begin' => 850, 'end' => 900),
          ),
        );
        $this->assertEquals($resOk, $res, "getPeriodsOfDays() sans ajustement");

        // ********************************************

        $res = $obj->getPeriodsOfDays(new \DateTime("2014-01-01 12:20:00"), new \DateTime("2014-01-03 12:20:00"), true);
        $resOk = Array(
          '2014-01-01' => array(
            array('begin' => 850, 'end' => 900),
          ),
          '2014-01-02' => array(
            array('begin' => 600, 'end' => 650),
            array('begin' => 850, 'end' => 900),
          ),
          '2014-01-03' => array(
            array('begin' => 600, 'end' => 650),
          ),
        );
        $this->assertEquals($resOk, $res, "getPeriodsOfDays() avec ajustement ");
    }

    function testNew_GetTimeSlots()
    {
        $obj = new Calendar();

        // ********************************************

        $res = $obj->getTimeSlotsOfDayPeriods(
          array(
            array('begin' => 600, 'end' => 700),
            array('begin' => 800, 'end' => 900),
          )
        );
        $resOk = array(
          array(
            'begin' => 600,
            'end' => 630,
          ),
          array(
            'begin' => 630,
            'end' => 660,
          ),
          array(
            'begin' => 660,
            'end' => 690,
          ),
          array(
            'begin' => 800,
            'end' => 830,
          ),
          array(
            'begin' => 830,
            'end' => 860,
          ),
          array(
            'begin' => 860,
            'end' => 890,
          ),
        );
        var_export($res);
        $this->assertEquals($resOk, $res, "getTimeSlotsOfDayPeriods()");

        // ********************************************

        $res = $obj->getTimeSlotsOfDays(new \DateTime("2014-01-01 12:20:00"), new \DateTime("2014-01-03 12:20:00"));
        $resOk = Array(
          '2014-01-01' => Array(),
          '2014-01-02' => Array(),
          '2014-01-03' => Array(),
        );
        $this->assertEquals($resOk, $res, "getTimeSlotsOfDays() sans ajustement");

        // ********************************************

        $res = $obj->getTimeSlotsOfDays(new \DateTime("2014-01-01 12:20:00"), new \DateTime("2014-01-03 12:20:00"), true);
        $resOk = Array(
          '2014-01-01' => Array(),
          '2014-01-02' => Array(),
          '2014-01-03' => Array(),
        );
        $this->assertEquals($resOk, $res, "getTimeSlotsOfDays() avec ajustement ");
    }

    function testSimpleOpenRules_GetTimeSlots()
    {
        $obj = new Calendar();
        $obj->addOpenRules([
          "p=600-700,800-900",
        ]);

        // ********************************************

        $res = $obj->getTimeSlotsOfDayPeriods(
          array(
            array('begin' => 600, 'end' => 700),
            array('begin' => 800, 'end' => 900),
          )
        );
        $resOk = array(
          array(
            'begin' => 600,
            'end' => 630,
          ),
          array(
            'begin' => 630,
            'end' => 660,
          ),
          array(
            'begin' => 660,
            'end' => 690,
          ),
          array(
            'begin' => 800,
            'end' => 830,
          ),
          array(
            'begin' => 830,
            'end' => 860,
          ),
          array(
            'begin' => 860,
            'end' => 890,
          ),
        );
        $this->assertEquals($resOk, $res, "getTimeSlotsOfDayPeriods()");

        // ********************************************

        $res = $obj->getTimeSlotsOfDays(new \DateTime("2014-01-01 12:20:00"), new \DateTime("2014-01-03 12:20:00"));
        $resOk = array(
          '2014-01-01' =>
          array(
            array(
              'begin' => 600,
              'end' => 630,
            ),
            array(
              'begin' => 630,
              'end' => 660,
            ),
            array(
              'begin' => 660,
              'end' => 690,
            ),
            array(
              'begin' => 800,
              'end' => 830,
            ),
            array(
              'begin' => 830,
              'end' => 860,
            ),
            array(
              'begin' => 860,
              'end' => 890,
            ),
          ),
          '2014-01-02' =>
          array(
            array(
              'begin' => 600,
              'end' => 630,
            ),
            array(
              'begin' => 630,
              'end' => 660,
            ),
            array(
              'begin' => 660,
              'end' => 690,
            ),
            array(
              'begin' => 800,
              'end' => 830,
            ),
            array(
              'begin' => 830,
              'end' => 860,
            ),
            array(
              'begin' => 860,
              'end' => 890,
            ),
          ),
          '2014-01-03' =>
          array(
            array(
              'begin' => 600,
              'end' => 630,
            ),
            array(
              'begin' => 630,
              'end' => 660,
            ),
            array(
              'begin' => 660,
              'end' => 690,
            ),
            array(
              'begin' => 800,
              'end' => 830,
            ),
            array(
              'begin' => 830,
              'end' => 860,
            ),
            array(
              'begin' => 860,
              'end' => 890,
            ),
          ),
        );
        $this->assertEquals($resOk, $res, "getTimeSlotsOfDays() sans ajustement");

        // ********************************************

        $res = $obj->getTimeSlotsOfDays(new \DateTime("2014-01-01 12:20:00"), new \DateTime("2014-01-03 12:20:00"), true);
        $resOk = array(
          '2014-01-01' =>
          array(
            array(
              'begin' => 800,
              'end' => 830,
            ),
            array(
              'begin' => 830,
              'end' => 860,
            ),
            array(
              'begin' => 860,
              'end' => 890,
            ),
          ),
          '2014-01-02' =>
          array(
            array(
              'begin' => 600,
              'end' => 630,
            ),
            array(
              'begin' => 630,
              'end' => 660,
            ),
            array(
              'begin' => 660,
              'end' => 690,
            ),
            array(
              'begin' => 800,
              'end' => 830,
            ),
            array(
              'begin' => 830,
              'end' => 860,
            ),
            array(
              'begin' => 860,
              'end' => 890,
            ),
          ),
          '2014-01-03' =>
          array(
            array(
              'begin' => 600,
              'end' => 630,
            ),
            array(
              'begin' => 630,
              'end' => 660,
            ),
            array(
              'begin' => 660,
              'end' => 690,
            ),
          ),
        );
        $this->assertEquals($resOk, $res, "getTimeSlotsOfDays() avec ajustement ");
    }

    function testSimpleOpenCloseRules_GetTimeSlots()
    {
        $obj = new Calendar();
        $obj->addOpenRules([
          "p=600-700",
          "p=800-900",
        ]);
        $obj->addCloseRules([
          "p=650-850",
          "p=1000-1100",
        ]);

        // ********************************************

        $res = $obj->getTimeSlotsOfDayPeriods(
          array(
            array('begin' => 600, 'end' => 650),
            array('begin' => 850, 'end' => 900),
          )
        );
        $resOk = array(
          array(
            'begin' => 600,
            'end' => 630,
          ),
          array(
            'begin' => 850,
            'end' => 880,
          ),
        );
        $this->assertEquals($resOk, $res, "getTimeSlotsOfDayPeriods()");

        // ********************************************

        $res = $obj->getTimeSlotsOfDays(new \DateTime("2014-01-01 12:20:00"), new \DateTime("2014-01-03 12:20:00"));
        $resOk = array(
          '2014-01-01' =>
          array(
            array(
              'begin' => 600,
              'end' => 630,
            ),
            array(
              'begin' => 850,
              'end' => 880,
            ),
          ),
          '2014-01-02' =>
          array(
            array(
              'begin' => 600,
              'end' => 630,
            ),
            array(
              'begin' => 850,
              'end' => 880,
            ),
          ),
          '2014-01-03' =>
          array(
            array(
              'begin' => 600,
              'end' => 630,
            ),
            array(
              'begin' => 850,
              'end' => 880,
            ),
          ),
        );
        $this->assertEquals($resOk, $res, "getTimeSlotsOfDays() sans ajustement");

        // ********************************************

        $res = $obj->getTimeSlotsOfDays(new \DateTime("2014-01-01 12:20:00"), new \DateTime("2014-01-03 12:20:00"), true);
        $resOk = array(
          '2014-01-01' =>
          array(
            array(
              'begin' => 850,
              'end' => 880,
            ),
          ),
          '2014-01-02' =>
          array(
            array(
              'begin' => 600,
              'end' => 630,
            ),
            array(
              'begin' => 850,
              'end' => 880,
            ),
          ),
          '2014-01-03' =>
          array(
            array(
              'begin' => 600,
              'end' => 630,
            ),
          ),
        );
        $this->assertEquals($resOk, $res, "getTimeSlotsOfDays() avec ajustement ");
    }

}
