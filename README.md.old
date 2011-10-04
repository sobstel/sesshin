Sesshin
=======

Advanced session management.

raw notes
---------

convention over configuration
what uses by default

conforms with psr-01 autoloading

requires hash extension (bundled since 5.1.2)

object-oriented, extendable session handling component written with security in mind
Written with security in mind, mitigates Session Hijacking, Session Fixation, Session Exposure, Sesion Poisoning, Session Prediction.

all set* method should be set before create/open!!!

features [zrobic tabelke cech i jakim atakom przeciwdzialaja!!!]
 * convenient way of assigning and accessing session values (array access, iterator, countable)
 * smart session expiry control
 * unlike PHP native mechanism, you don't have to use cron or resourse-consuming 100% garbage collecting probability to ensure sessions are removed exactly after specified time
 * prevents session adoption, i.e. session ids generated only by the component are acceptable (strict model)
 * session id rotation (anti session hijacking)
   - possibility of regenerating session id whenever you want (unlike PHP default behaviour, scSession removes old session after generating new one)
   - configurable (it can be automatically regenerated after X number of requests or/and after specified time)
 * support for user-defined storage
 * support for user-defined listeners (observers) - event handling
   - onExpiry - session expiry event
   - onInvalid - session id invalid event (when session expired and removed from storage or cookie modified manualy)
 * support for user-defined entropy callback
 * wide range of hash algorithms (hash extension used)
 * possibility of continuing only already existing sessions (not doing cookies, store or anything for non-existing)
 * support for own fingerprint generators via callback, e.g. user agent or ip (ip discouraged!)

composite storage, can assign a few, e.g. apc, file (e.g. in case apc is filled up)

$composite = new kSession_Storage_Composite(
	new kSession_Storage_Apc(), new kSession_Storage_File()
);

// $sess = new kSession('4enigma', new kSession_Storage_Apc());
// $sess = new kSession('enigma3', new kSession_Storage_File());
$sess = new kSession('exits', $composite);
$sess->setGcProbability(100);
$sess->open(true);
