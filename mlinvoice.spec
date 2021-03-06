Name:		mlinvoice
Version:	1.9.0
Release:	1%{?dist}
Summary:	MLInvoice - Web application to create Finnish invoices
Group:		Applications/Internet
License:	GPLv2
URL:		http://www.labs.fi/
Source0:	%{name}-%{version}%{?prever}.tar.bz2
BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
Obsoletes: vllasku

BuildRequires:	httpd
Requires:	httpd
Requires:	php
Requires:	php-mysql
%if 0%{?el5}
Requires:	php-pecl-json
%endif
Requires:	php-mbstring
Requires: php-xml
Requires: php-xsl
BuildArch:	noarch

%description
MLInvoice is a web application written in PHP for printing invoices. 
It available in English and Finnish. Among its features 
are automatic invoice numbering and reference calculation, pdf 
generation, customer database and unlimited number of user accounts. 
Data is stored in a MySQL database.

%prep
%setup -q -n %{name}-%{version}%{?prever}

%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -d -m 755 $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d

cat > $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/%{name}.conf <<EOM

Alias /%{name} %{_datadir}/%{name}

<Location /%{name}>
AddDefaultCharset UTF-8
php_value include_path      ".:%{_sysconfdir}/%{name}"
</Location>
EOM

%{__install} -d -m755 $RPM_BUILD_ROOT%{_sysconfdir}/%{name}
%{__install} -d -m755 $RPM_BUILD_ROOT%{_datadir}/%{name}

%{__install} -m644 *.php *.ico *.xsl *.xsd config.php.sample $RPM_BUILD_ROOT%{_datadir}/%{name}
%{__cp} -a css datatables images jquery js lang select2 tcpdf $RPM_BUILD_ROOT%{_datadir}/%{name}

%{__mv} $RPM_BUILD_ROOT%{_datadir}/%{name}/config.php.sample \
	$RPM_BUILD_ROOT%{_sysconfdir}/%{name}/config.php

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%doc LICENSE README.md create_database.sql update_database_1.0_to_1.1.sql update_database_1.1_to_1.2.sql update_database_1.2_to_1.3.sql update_database_1.3_to_1.4.sql update_database_1.4_to_1.5.sql
%config(noreplace) %{_sysconfdir}/httpd/conf.d/%{name}.conf
%attr(2755,root,apache) %dir %{_sysconfdir}/%{name}
%attr(0640,root,apache) %config(noreplace) %{_sysconfdir}/%{name}/config.php
%{_datadir}/%{name}

%changelog
* Tue Mar 05 2013 Ere Maijala <ere@labs.fi> - 1.9.0-1
- updated for version 1.9.0
- added lang and select2 directories
* Tue Mar 05 2013 Ere Maijala <ere@labs.fi> - 1.8.0-1
- updated for version 1.8.0
* Wed Feb 13 2013 Ere Maijala <ere@labs.fi> - 1.7.0-1
- updated for version 1.7.0
* Mon Oct 5 2012 Ere Maijala <ere@labs.fi> - 1.6.1-1
- updated for version 1.6.1
* Sat Jul 7 2012 Ere Maijala <ere@labs.fi> - 1.6.0-1
- rebranded and updated for version 1.6.0
* Sat Jun 2 2012 Ere Maijala <ere@labs.fi> - 1.5.3-1
- updated for version 1.5.3
* Wed May 23 2012 Ere Maijala <ere@labs.fi> - 1.5.2-1
- updated for version 1.5.2
* Sun May 20 2012 Ere Maijala <ere@labs.fi> - 1.5.1-1
- updated for version 1.5.1
* Sun Mar 18 2012 Ere Maijala <ere@labs.fi> - 1.5.1-1
- updated for version 1.5.1
* Sun Mar 18 2012 Ere Maijala <ere@labs.fi> - 1.5.0-1
- updated for version 1.5.0
* Wed Jan 11 2012 Ere Maijala <ere@labs.fi> - 1.4.3-1
- updated for version 1.4.3
* Mon Jan 9 2012 Ere Maijala <ere@labs.fi> - 1.4.2-1
- updated for version 1.4.2
- Added php-xml and php-xsl to requirements
* Sat Jan 7 2012 Ere Maijala <ere@labs.fi> - 1.4.1-1
- updated for version 1.4.1
* Sat Dec 3 2011 Ere Maijala <ere@labs.fi> - 1.4.0-1
- updated for version 1.4.0
* Fri Jun  3 2011 Ere Maijala <ere@labs.fi> - 1.3.0-1
- initial spec from Mika Ilmaranta <ilmis@foobar.fi>
