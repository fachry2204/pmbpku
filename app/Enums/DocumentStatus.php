<?php
namespace App\Enums;
enum DocumentStatus:string { case PendingReview='pending_review'; case Complete='complete'; case Incomplete='incomplete'; case RevisionSubmitted='revision_submitted'; }
