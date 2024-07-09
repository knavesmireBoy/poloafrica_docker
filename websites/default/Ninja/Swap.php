<?php

namespace Ninja;

class Swap extends Box
{

    public function update(\Ninja\Pkg $pkg, $prev_addr)
    {
        $former = $this->find($pkg->address);
        $latter = $this->find($prev_addr);
        $demoted = $former->getPackage('type');
        $promoted = $latter->getPackage('type');
        if (equals($promoted, $demoted)) {
            $latter->set($former->getPackage());
        }
        /* 
        The gallery layout is fixed. A picture is allocated at a slot (or box). The database table for modelling this layout consists of the following columns: BOX_ID | ORIENTATION | PC_ID
        with BOX_ID numbered 1 to n (17 in this scenario).
        To move a pic we end up assigning a new PIC_ID at BOX_ID (eg BOX_ID 1 : PIC_ID 10 could become
        BOX_ID 1 : PIC_ID 20 or BOX_ID 4 : PIC_ID 10)
        In a shuffle scenario a picture "REQUESTS" to move to a TARGET location from a SOURCE location
        All SUBSEQUENT pictures are shunted forward one UNTIL the SOURCE location is reached.
        At this point iteration stops as no further adjustments are required and the system recognises that the REQUEST location was already vacated.
        IF all pictures were equal SIZES (advisable) all would be well. The current address (BOX_ID) is incremented by 1 and placed at the new location.
        The major CONSTRAINT is that a LANDSCAPE picture cannot land at a PORTRAIT target
        and in that scenario the ADDRESS has to be incremented by 1 in order to skip over the portrait pic location.
        (HYPOTHETICAL IF there were a SEQUENCE of portraits further incrementation would be requred)
         By DEFAULT iteration starts at the HEAD of the CHAIN OF RESPONSIBILITY collection and IF the TARGET location comes AFTER a portrait pic then the extra incrementation would happen BEFORE the target is reached with the result that the picture is inserted one place AFTER the REQUESTED location. To avoid this SCENARIO iteration must begin at the target location.
         If I were you, I WOULDN'T start from here: $this->handle();
         */
        $former->handle();
    }
}
