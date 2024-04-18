# AuerswaldGroupManager

## Background

Auerswald PBX systems support an external DECT manager in order to communicate with cordless handsets.

Compared to regular "SysTel" system-enabled telephones however, the functionality of the DECT handsets
is limited, especially when dealing with multiple external lines.

Call grouping allows the mobile handset to receive calls coming in on multiple lines easily.  
Picking the outgoing line however is cumbersome and requires prefixing the number
with the exchange line identifier, e.g. `**201`. [Reference](https://docs.auerswald.de/COMpact_5200_5500_R/Help_en_12/index.html#page/Buch1/gezieltvoipzugang_reference.html)  
The alternative is to use the ability to control the chosen line by using the outgoing group
login functionality. For this, the dial code `## 8 * 42 1 <Group> #` is used. [Reference](https://docs.auerswald.de/COMpact_5200_5500_R/Help_en_12/index.html#page/Buch1/gruppen_reference.html)  
Short-Code Macros can help make this a little easier, but it is still not a good solution. [Reference](https://docs.auerswald.de/COMpact_5200_5500_R/Help_en_12/index.html#page/Buch1/kurzwahlmakros_verwaltung_reference.html)

The COMfortel M-5x0 and the M-7x0 handsets however do support a feature called [Gigaset Remote Access Protocol](https://teamwork.gigaset.com/gigawiki/display/GPPPO/RAP+2.1) which is a small text-only "browser" for xhtml pages running on the handset using the [CAT-iq](https://en.wikipedia.org/wiki/CAT-iq) standard to communicate to the base station which does the actual http request.

Using this feature, one can write simple applications such as a text-based interface to the PBX system allowing for management of groups log-in status, which is what the AuerswaldGroupManager does.

## Installation

The AGM is a collection of php scripts, intended to run self-contained on a webserver.

### Prerequisites

- Auerswald COMpact PBX system. COMpact 5000 and 5200 were tested. COMmander should work too, but not tested.
- Auerswald COMfortel WS-500S or WS-500M DECT system.
- PHP enabled webserver

### Webserver installation

- Download the files
- Rename `group_manager.cfg.php.sample` to `group_manager.cfg.php` and fill with your site specific configuration
- Test using curl, e.g. `curl -H "User-Agent: Auerswald COMfortel WS-500M/V2.53.0+build.c0ed5a2;000952C0FFEE" "http://192.168.0.242/group_manager.xhtml.php?lang=2&tz=0&mac=000952C0FFEE&cc=49&handsetid=0355C0FFEE&sipid=<sip_extension>&provid=1` to see that you're getting some xhtml return.

## Base Station configuration

Add the following XML snippet to the provisioning file for the Auerswald COMfortel WS-500x DECT manager.  
There can be a maximum of 4 RAP services defined using the IDs 3 to 7.  
_ServerURL_ needs to point to the location of your GroupManager installation.

```
<?xml version="1.0" encoding="UTF-8"?>
<provisioning version="1.1" productID="e2">
  <nvm>
    <!-- xhtml RAP service configuration -->
    <param name="RapService.3.Activated" value="1"/>
    <param name="RapService.3.Name" value="Gruppenverwaltung"/>
    <param name="RapService.3.ServerURL" value="http://192.168.0.242/group_manager.xhtml.php"/>
    <param name="RapService.3.SoftkeyName" value="Gruppen"/>
    <param name="RapService.3.UseSIP" value="1"/>
    <param name="DmGlobal.0.AddSipId" value="1"/>
  </nvm>
</provisioning>
```

[Gigaset reference documentation](https://teamwork.gigaset.com/gigawiki/pages/viewpage.action?pageId=828671569)

## Usage

Once configured and the base station has been reporivisioned, the group manager can be accessed by opening the
handset menu and pressing `2` for "Info Services".  
This will load the group manager website which is communicating with the Auerswald PBX to find available groups and offer
convenient visual UI to select the groups the handset is logged in.
The AGM code will then update the PBX via HTTP calls with the new group configuration.

## Development Status

The application works fine for the intended use but it is a first draft implementation.  
Authentication is very rudimentary and will only verify that the connection comes from a trusted IP
as configured in the `group_manager.cfg.php` file.

Furthermore, the application assumes a well-behaved client as extensive input validation is not in place yet.
