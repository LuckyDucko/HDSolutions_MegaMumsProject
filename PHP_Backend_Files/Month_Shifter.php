<?php

function MonthShifter (DateTime $aDate,$months)
{
        $dateA = clone($aDate);
        $dateB = clone($aDate);
        $plusMonths = clone($dateA->modify($months . ' Month'));
        
        //check whether reversing the month addition gives us the original day back
        if($dateB != $dateA->modify($months*-1 . ' Month'))
        {
            $result = $plusMonths->modify('last day of last month');
        } 
        elseif($aDate == $dateB->modify('last day of this month'))
        {
            $result =  $plusMonths->modify('last day of this month');
        } 
        else 
        {
            $result = $plusMonths;
        }
        return $result;
    }
?>