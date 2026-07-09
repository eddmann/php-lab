/* Stubs for POSIX symbols missing under Emscripten. None of these paths are
 * exercised by the lab (no Fibers, no proc_open, no privilege drops):
 * - get/make/swapcontext: Zend Fibers' ucontext fallback (Fiber unusable, fine)
 * - posix_spawnp: proc_open (returns ENOSYS -> proc_open fails cleanly)
 * - getdtablesize / initgroups: trivial values
 */
#include <errno.h>
#include <stddef.h>

int getcontext(void *ucp) { (void)ucp; errno = ENOSYS; return -1; }
void makecontext(void *ucp, void (*fn)(void), int argc, ...) { (void)ucp; (void)fn; (void)argc; }
int swapcontext(void *o, const void *n) { (void)o; (void)n; errno = ENOSYS; return -1; }
int posix_spawnp(void *pid, const char *file, const void *fa, const void *attr,
                 char *const argv[], char *const envp[])
{ (void)pid; (void)file; (void)fa; (void)attr; (void)argv; (void)envp; return ENOSYS; }
int getdtablesize(void) { return 1024; }
int initgroups(const char *user, unsigned int group) { (void)user; (void)group; return 0; }
