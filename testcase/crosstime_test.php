<?php
require_once('simpletest/autorun.php');
require_once('../Classes/EFObject.php');

class TestOfCrossTime extends UnitTestCase {
    function testCrossTime() {
        $OutputFormat=0;
        $crosstime=new CrossTime ("", "2012-04-04", "", "14:08:00", "+08:00 CST", "2012-04-04 14:8:00", 0); $datetime=$crosstime->stringInZone("+08:00 CST");
        $this->assertEqual($datetime,"2:08PM on Wed, Apr 4");

        $crosstime=new CrossTime ("", "2012-04-04", "", "14:08:00", "+08:00 CST", "2012-04-04 14:8:00 abc", 1); $datetime=$crosstime->stringInZone("+08:00 CST");
        $this->assertEqual($datetime,"2012-04-04 14:8:00 abc");

        $crosstime=new CrossTime ("This Week", "", "", "", "+08:00 CST", "2012-04-04 14:8:00", 0); $datetime=$crosstime->stringInZone("+08:00 CST");
        $this->assertEqual($datetime,"This Week");

        $crosstime=new CrossTime ("", "2012-04-04", "", "", "+08:00 CST", "2012-04-04 14:8:00", 0); $datetime=$crosstime->stringInZone("+08:00 CST");
        $this->assertEqual($datetime,"Wed, Apr 4");

        $crosstime=new CrossTime ("", "", "Dinner", "", "+08:00 CST", "2012-04-04 14:8:00", 0); $datetime=$crosstime->stringInZone("+08:00 CST");
        $this->assertEqual($datetime,"Dinner");

        $crosstime=new CrossTime ("", "", "", "14:08:00", "+08:00 CST", "2012-04-04 14:8:00", 0); $datetime=$crosstime->stringInZone("+08:00 CST");
        $this->assertEqual($datetime,"2:08PM");

        $crosstime=new CrossTime ("This Week", "2012-04-04", "", "", "+08:00 CST", "2012-04-04 14:8:00", 0); $datetime=$crosstime->stringInZone("+08:00 CST");
        $this->assertEqual($datetime,"This Week on Wed, Apr 4");

        $crosstime=new CrossTime ("This Week", "", "Dinner", "", "+08:00 CST", "2012-04-04 14:8:00", 0); $datetime=$crosstime->stringInZone("+08:00 CST");
        $this->assertEqual($datetime,"Dinner This Week");

        $crosstime=new CrossTime ("This Week", "", "", "14:08:00", "+08:00 CST", "2012-04-04 14:8:00", 0); $datetime=$crosstime->stringInZone("+08:00 CST");
        $this->assertEqual($datetime,"2:08PM This Week");

        $crosstime=new CrossTime ("", "2012-04-04", "Dinner", "", "+08:00 CST", "2012-04-04 14:8:00", 0); $datetime=$crosstime->stringInZone("+08:00 CST");
        $this->assertEqual($datetime,"Dinner on Wed, Apr 4");

        $crosstime=new CrossTime ("", "2012-04-04", "", "14:08:00", "+08:00 CST", "2012-04-04 14:8:00", 0); $datetime=$crosstime->stringInZone("+08:00 CST");
        $this->assertEqual($datetime,"2:08PM on Wed, Apr 4");

        $crosstime=new CrossTime ("", "", "Dinner", "14:08:00", "+08:00 CST", "2012-04-04 14:8:00", 0); $datetime=$crosstime->stringInZone("+08:00 CST");
        $this->assertEqual($datetime,"Dinner at 2:08PM");

        $crosstime=new CrossTime ("", "", "Dinner", "14:08:00", "+08:00 CST", "2012-04-04 14:8:00", 0); $datetime=$crosstime->stringInZone("+08:00 CST");
        $this->assertEqual($datetime,"Dinner at 2:08PM");

        $crosstime=new CrossTime ("This Week", "2012-04-04", "Dinner", "", "+08:00 CST", "2012-04-04 14:8:00", $OutputFormat);$datetime=$crosstime->stringInZone("+08:00 CST");
        $this->assertEqual($datetime,"Dinner This Week on Wed, Apr 4");

        $crosstime=new CrossTime ("This Week", "2012-04-04", "", "14:08:00", "+08:00 CST", "2012-04-04 14:8:00", $OutputFormat);$datetime=$crosstime->stringInZone("+08:00 CST");       
        $this->assertEqual($datetime,"2:08PM This Week on Wed, Apr 4");

        $crosstime=new CrossTime ("This Week", "", "Dinner", "14:08:00", "+08:00 CST", "2012-04-04 14:8:00", $OutputFormat);$datetime=$crosstime->stringInZone("+08:00 CST");           
        $this->assertEqual($datetime,"Dinner at 2:08PM This Week");

        $crosstime=new CrossTime ("This Week", "2012-04-04", "Dinner", "14:08:00", "+08:00 CST", "2012-04-04 14:8:00", $OutputFormat);$datetime=$crosstime->stringInZone("+08:00 CST"); 
        $this->assertEqual($datetime,"Dinner at 2:08PM This Week on Wed, Apr 4");
#
#    {CrossTime{EFTime{"", "2012-04-04", "", "14:08:00", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},                "+08:00", true, "2:08PM on Wed, Apr 4"},
#    {CrossTime{EFTime{"", "2012-04-04", "", "14:08:00", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},                "", true, "2:08PM on Wed, Apr 4"},
#    {CrossTime{EFTime{"", "2012-04-04", "", "14:08:00", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},                "+08:00 PST", true, "2:08PM on Wed, Apr 4"},
#
#    {CrossTime{EFTime{"", "2012-04-04", "", "14:08:00", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},                "+09:00 PST", true, "3:08PM +09:00 PST on Wed, Apr 4"},
#    {CrossTime{EFTime{"", "2012-04-04", "", "14:08:00", "+08:00 CST"}, "2012-04-04 14:8:00 abc", OutputOrigin},            "+09:00 PST", true, "2012-04-04 14:8:00 abc +08:00 CST"},
#
#    {CrossTime{EFTime{"This Week", "", "", "", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},                         "+09:00 PST", true, "This Week"},
#    {CrossTime{EFTime{"", "2012-04-04", "", "", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},                        "+09:00 PST", true, "Wed, Apr 4"},
#    {CrossTime{EFTime{"", "", "Dinner", "", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},                            "+09:00 PST", true, "Dinner +08:00 CST"},
#    {CrossTime{EFTime{"", "", "", "14:08:00", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},                          "+09:00 PST", true, "3:08PM +09:00 PST"},
#    {CrossTime{EFTime{"This Week", "2012-04-04", "", "", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},               "+09:00 PST", true, "This Week on Wed, Apr 4"},
#    {CrossTime{EFTime{"This Week", "", "Dinner", "", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},                   "+09:00 PST", true, "Dinner +08:00 CST This Week"},
#    {CrossTime{EFTime{"This Week", "", "", "14:08:00", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},                 "+09:00 PST", true, "3:08PM +09:00 PST This Week"},
#    {CrossTime{EFTime{"", "2012-04-04", "Dinner", "", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},                  "+09:00 PST", true, "Dinner +08:00 CST on Wed, Apr 4"},
#    {CrossTime{EFTime{"", "2012-04-04", "", "14:08:00", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},                "+09:00 PST", true, "3:08PM +09:00 PST on Wed, Apr 4"},
#    {CrossTime{EFTime{"", "", "Dinner", "14:08:00", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},                    "+09:00 PST", true, "Dinner at 3:08PM +09:00 PST"},
#    {CrossTime{EFTime{"This Week", "2012-04-04", "Dinner", "", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},         "+09:00 PST", true, "Dinner +08:00 CST This Week on Wed, Apr 4"},
#    {CrossTime{EFTime{"This Week", "2012-04-04", "", "14:08:00", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},       "+09:00 PST", true, "3:08PM +09:00 PST This Week on Wed, Apr 4"},
#    {CrossTime{EFTime{"This Week", "", "Dinner", "14:08:00", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},           "+09:00 PST", true, "Dinner at 3:08PM +09:00 PST This Week"},
#    {CrossTime{EFTime{"This Week", "2012-04-04", "Dinner", "14:08:00", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat}, "+09:00 PST", true, "Dinner at 3:08PM +09:00 PST This Week on Wed, Apr 4"},
#
#    {CrossTime{EFTime{"", "2012-04-04", "", "14:08:00", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},                "+09:00", true, "3:08PM +09:00 on Wed, Apr 4"},
#    {CrossTime{EFTime{"", "2012-04-04", "", "14:08:00", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},                "", true, "2:08PM on Wed, Apr 4"},
#    {CrossTime{EFTime{"", "2012-04-04", "", "14:08:00", "+08:00 CST"}, "2012-04-04 14:8:00", OutputFormat},                "+09:00 PST", true, "3:08PM +09:00 PST on Wed, Apr 4"},
#

    }
}

