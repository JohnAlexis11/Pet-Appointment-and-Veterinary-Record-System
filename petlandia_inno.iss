[Setup]
AppName=PetLandiaSystem
AppVersion=1.0
DefaultDirName={pf}\Petlandia 2.0
DefaultGroupName=Petlandia 2.0
OutputBaseFilename=PetLandiaSystemSetup
Compression=lzma
SolidCompression=yes

[Files]
Source: "/mnt/data/Petlandia_edited/phpdesktop_ready\*"; DestDir: "{app}"; Flags: recursesubdirs createallsubdirs

[Icons]
Name: "{group}\PetLandia"; Filename: "{app}\phpdesktop-chrome.exe"
Name: "{commondesktop}\PetLandia"; Filename: "{app}\phpdesktop-chrome.exe"

[Run]
Filename: "{app}\phpdesktop-chrome.exe"; Description: "Launch PetLandia"; Flags: nowait postinstall skipifsilent