# Licence

Copyright (c) 2026 Brent Passmore. All rights reserved.

The source of this software is published so that it can be installed with
Composer, inspected by the people who rely on it, and audited by the people they
answer to. Publishing the source is not a grant of ownership.

**This software is free.** There is nothing to buy, no licence key, and no
limit on how many sites you run it on. It is not, however, public domain, and
the difference matters: see condition 1.

Permission is granted to any person obtaining a copy of this software (the
"Software") to use, copy and modify it, on any number of sites, subject to the
conditions below.

1. **Not for resale or reuse in another product.** The Software, in whole or in
   part, may not be redistributed, resold, sublicensed, or reused as the basis of
   another product without written permission.

   This is the condition the whole licence exists for. These rules are a
   deliberate fork of the accessibility engine behind another product by the
   same author. A permissive licence on the addon would give that engine away
   too, to anyone, competitors included. Free to use is the intent. Free to
   take is not.

   Reading the code to learn from it is expected and encouraged. Shipping it as
   your own is not.

2. **Keep the notice.** This licence and the copyright notice stay with any copy
   or substantial portion of the Software.

3. **Follow the law.** Use of the Software must not break any applicable law or
   regulation, nor infringe anyone else's rights.

Failing any of these conditions ends the permission granted here, immediately and
automatically.

## No warranty, and specifically no conformance claim

The capitals are below, as usual. This part is in plain words because it is the
part people actually need.

This Software cannot tell you that a site is accessible, and nothing it
produces is a conformance claim. Automated checking finds a subset of a subset:
it reads rendered HTML, it catches what is mechanically detectable, and a great
deal of WCAG 2.2 AA is not mechanically detectable at all. A page this Software
refuses to publish has a problem. A page it allows has not been proven to have
none.

Some checks in this Software cannot run at all in some installations, because
they depend on markup the host does not produce. Where that is true the Software
reports it. Read what it says ran, not just what it found.

Deciding whether a site meets a legal or contractual accessibility obligation is
work for a person, and this Software does not do it, replace it, or provide
evidence sufficient for it.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE, ACCESSIBILITY CONFORMANCE, AND NONINFRINGEMENT. IN NO
EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR
OTHER LIABILITY, INCLUDING SPECIAL, INCIDENTAL AND CONSEQUENTIAL DAMAGES, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

---

*This licence was drafted by reading Statamic's own, which is the norm this
addon is listed alongside. No lawyer has reviewed it. The conformance
disclaimer is the clause most likely to be tested one day, and giving the
software away for free does not change that: a tool somebody relied on is a
tool somebody relied on.*
