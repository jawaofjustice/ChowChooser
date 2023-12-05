import time
from datetime import datetime
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service as ChromeService
from webdriver_manager.chrome import ChromeDriverManager
		
HEADLESS = False		

class Scraper:
	def __init__(self, HEADLESS):
		self.startTime = datetime.now()
		self.headless = HEADLESS
		
		print("Initializing scraper at " + str(self.startTime))
		
		try:
			self.loadChromedriver()
			self.scrape()
			self.outputToFile()
		except Exception as err:
			print("Script has exited early due to an error: " + str(err))
				
		finally:
			self.shutdownScraper()
	
	def loadChromedriver(self):
		print("Loading chromedriver...")
		options = Options()  	
		if self.headless:
			self.say("Running in headless mode...")
			#options.add_argument('--no-sandbox') -- possibly breaking chrome quit() on exit / crash
			options.add_argument("--headless")
			options.add_argument('--disable-gpu')
			options.add_argument("--window-size=1920, 1200")
			options.add_argument("--start-maximized") # open Browser in maximized mode
			options.add_argument("--disable-infobars") # disabling infobars
			options.add_argument("--disable-extensions") # disabling extensions
			options.add_argument("--disable-dev-shm-usage") # overcome limited resource problems
			options.add_argument("--ignore-certificate-errors") # pass cert warnings
			options.add_argument("enable-automation")
			options.add_argument("--dns-prefetch-disable")
		
		self.driver = webdriver.Chrome(service=ChromeService(ChromeDriverManager().install()), options=options)
		
		print("Chromedriver loaded!")
		
		
	def scrape(self):
		print("Commencing scrape operation...")
		self.driver.get("http://grubhub.com")
		print("Finished scraping!")
		
	
	def outputToFile(self):
		print("Outputting results to file...")
		print("File output complete!")
		
	def shutdownScraper(self):
		time.sleep(10)
		if hasattr(self, "driver"):
			self.driver.quit()
		self.endTime = datetime.now()
		self.runTime = self.endTime - self.startTime
		print("Scraper is now shutting down at " + str(self.endTime) + " after running for " + str(self.runTime) + ".")
		


scraper = Scraper(HEADLESS)
